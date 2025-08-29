<?php

namespace happycog\craftmcp\transports;

use Craft;
use craft\helpers\App;
use craft\web\Request;
use craft\web\Response;
use Evenement\EventEmitterTrait;
use PhpMcp\Schema\JsonRpc\Error;
use PhpMcp\Schema\JsonRpc\Parser;
use PhpMcp\Server\Contracts\ServerTransportInterface;
use PhpMcp\Server\Session\SessionManager;
use PhpMcp\Schema\JsonRpc\Message;
use React\Promise\PromiseInterface;
use Throwable;
use yii\web\BadRequestHttpException;

use function React\Promise\resolve;

class HttpServerTransport implements ServerTransportInterface
{
    use EventEmitterTrait;

    protected SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;

        $this->on('message', function (Message $message, string $sessionId) {
            $session = $this->sessionManager->getSession($sessionId);
            if ($session !== null) {
                $session->save(); // This updates the session timestamp
            }
        });
    }

    protected function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * For this integrated transport, 'listen' doesn't start a network listener.
     * It signifies the transport is ready to be used by the Protocol handler.
     * The actual listening is done by Craft's web application.
     */
    public function listen(): void
    {
        $this->emit('ready');
    }

    /**
     * Sends a message to a specific client session by queueing it in the SessionManager.
     * The SSE streams will pick this up.
     */
    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface
    {
        $rawMessage = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (empty($rawMessage)) {
            return resolve(null);
        }

        $this->sessionManager->queueMessage($sessionId, $rawMessage);

        return resolve(null);
    }

    /**
     * Handle incoming HTTP POST message requests
     */
    public function handleMessageRequest(Request $request, Response $response): Response
    {
        $this->collectSessionGarbage();

        if (!$request->getIsJson()) {
            Craft::warning('Received POST request with invalid Content-Type', __METHOD__);

            $error = Error::forInvalidRequest('Content-Type must be application/json');

            $response->format = Response::FORMAT_JSON;
            $response->data = $error;
            $response->statusCode = 415;
            return $response;
        }

        $sessionId = $request->getQueryParam('clientId');
        if (!$sessionId || !is_string($sessionId)) {
            Craft::error('Received POST request with missing or invalid sessionId', __METHOD__);

            $error = Error::forInvalidRequest('Missing or invalid clientId query parameter');

            $response->format = Response::FORMAT_JSON;
            $response->data = $error;
            $response->statusCode = 400;
            return $response;
        }

        $content = $request->getRawBody();
        if (empty($content)) {
            Craft::warning('Received POST request with empty body', __METHOD__);

            $error = Error::forInvalidRequest('Empty body');

            $response->format = Response::FORMAT_JSON;
            $response->data = $error;
            $response->statusCode = 400;
            return $response;
        }

        try {
            $message = Parser::parse($content);
        } catch (Throwable $e) {
            Craft::error('MCP: Failed to parse message: ' . $e->getMessage(), __METHOD__);

            $error = Error::forParseError('Invalid JSON-RPC message: ' . $e->getMessage());

            $response->format = Response::FORMAT_JSON;
            $response->data = $error;
            $response->statusCode = 400;
            return $response;
        }

        $this->emit('message', [$message, $sessionId]);

        $response->format = Response::FORMAT_JSON;
        $response->data = [
            'jsonrpc' => '2.0',
            'result' => null,
            'id' => 1,
        ];
        $response->statusCode = 202;
        return $response;
    }

    /**
     * Handle SSE connection requests
     */
    public function handleSseRequest(Request $request, Response $response): Response
    {
        $sessionId = $this->generateId();

        $this->emit('client_connected', [$sessionId]);

        $pollInterval = (int) App::parseEnv('$MCP_SSE_POLL_INTERVAL', 1);
        if ($pollInterval < 1) {
            $pollInterval = 1;
        }

        // Set SSE headers
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->stream = function () use ($sessionId, $pollInterval, $request) {
            @set_time_limit(0);

            try {
                // Generate the POST endpoint URI - we'll need to create a URL rule for this
                $postEndpointUri = "/mcp/sse-transport/message?clientId={$sessionId}";

                $this->sendSseEvent('endpoint', $postEndpointUri, "mcp-endpoint-{$sessionId}");
            } catch (Throwable $e) {
                Craft::error('Error sending initial endpoint event - sessionId: ' . $sessionId . ', exception: ' . $e->getMessage(), __METHOD__);
                return;
            }

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $messages = $this->sessionManager->dequeueMessages($sessionId);
                foreach ($messages as $message) {
                    $this->sendSseEvent('message', rtrim($message, "\n"));
                }

                static $keepAliveCounter = 0;
                $keepAliveInterval = (int) round(15 / $pollInterval);
                if (($keepAliveCounter++ % $keepAliveInterval) == 0) {
                    echo ": keep-alive\n\n";
                    $this->flushOutput();
                }

                usleep($pollInterval * 1000000);
            }

            $this->emit('client_disconnected', [$sessionId, 'SSE stream closed']);
        };

        return $response;
    }

    /**
     * Send an SSE event
     */
    private function sendSseEvent(string $event, string $data, ?string $id = null): void
    {
        if (connection_aborted()) {
            return;
        }

        echo "event: {$event}\n";
        if ($id !== null) {
            echo "id: {$id}\n";
        }

        foreach (explode("\n", $data) as $line) {
            echo "data: {$line}\n";
        }

        echo "\n";
        $this->flushOutput();
    }

    /**
     * Flush output buffer
     */
    protected function flushOutput(): void
    {
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        @flush();
    }

    protected function collectSessionGarbage(): void
    {
        // Use Craft's cache component or a simple probability-based approach
        $lottery = [2, 100]; // Default lottery from Laravel config
        
        if (random_int(1, $lottery[1]) <= $lottery[0]) {
            $this->sessionManager->gc();
        }
    }

    /**
     * 'Closes' the transport.
     */
    public function close(): void
    {
        $this->emit('close', ['Transport closed.']);
        $this->removeAllListeners();
    }
}
