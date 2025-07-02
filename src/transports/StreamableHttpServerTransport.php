<?php

namespace markhuot\craftmcp\transports;

use Craft;
use craft\web\Request;
use craft\web\Response;
use PhpMcp\Server\Contracts\ServerTransportInterface;
use PhpMcp\Schema\JsonRpc\Message;
use PhpMcp\Schema\JsonRpc\Parser;
use Evenement\EventEmitterTrait;
use React\Promise\PromiseInterface;
use React\Promise\Promise;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class StreamableHttpServerTransport implements ServerTransportInterface
{
    use EventEmitterTrait;

    protected array $sessions = [];
    protected ?string $currentSessionId = null;
    protected bool $listening = false;

    public function listen(): void
    {
        $this->listening = true;
        $this->emit('ready');
    }

    public function close(): void
    {
        $this->listening = false;
        
        // Disconnect all sessions
        foreach (array_keys($this->sessions) as $sessionId) {
            $this->emit('client_disconnected', [$sessionId, 'Transport closed']);
        }
        
        $this->sessions = [];
        $this->emit('close', ['Transport closed']);
    }

    protected ?array $pendingResponse = null;
    protected ?string $pendingSessionId = null;

    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface
    {
        return new Promise(function ($resolve, $reject) use ($message, $sessionId, $context) {
            if (!isset($this->sessions[$sessionId])) {
                $reject(new \Exception("Session not found: $sessionId"));
                return;
            }

            // Store message for SSE streaming
            $this->sessions[$sessionId]['messages'][] = [
                'message' => $message,
                'context' => $context,
                'timestamp' => time()
            ];

            // Convert Message object to array for JSON response
            $messageData = $message;
            if (method_exists($message, 'jsonSerialize')) {
                $messageData = $message->jsonSerialize();
            } elseif (method_exists($message, 'toArray')) {
                $messageData = $message->toArray();
            }

            // Store for immediate return
            $this->pendingResponse = $messageData;
            $this->pendingSessionId = $sessionId;

            $resolve(null);
        });
    }

    /**
     * Handle POST requests - process MCP messages
     */
    public function handlePost(Request $request, Response $response): Response
    {
        if (!$request->getIsJson()) {
            throw new BadRequestHttpException('Content-Type must be application/json');
        }

        $sessionId = $request->getQueryParam('sessionId');
        if (!$sessionId) {
            $sessionId = $this->generateSessionId();
        }

        $this->currentSessionId = $sessionId;
        $this->initializeSession($sessionId);

        $rawBody = $request->getRawBody();
        $body = $request->getBodyParams();
        
        // If we don't have parsed body params, try to parse the raw body
        if (empty($body)) {
            $body = json_decode($rawBody, true);
        }

        if (!$body || !isset($body['method'])) {
            throw new BadRequestHttpException('Invalid JSON-RPC request');
        }

        try {
            // Parse the JSON-RPC message - Parser expects JSON string
            $jsonString = is_string($rawBody) ? $rawBody : json_encode($body);
            $message = Parser::parse($jsonString);
            
            // Try to get the server from container and process message directly
            try {
                $server = \Craft::$container->get(\PhpMcp\Server\Server::class);
                $protocol = $server->getProtocol();
                
                // Ensure the protocol is bound to this transport
                $protocol->bindTransport($this);
                
                // Clear any pending response
                $this->pendingResponse = null;
                $this->pendingSessionId = null;
                
                // Process the message directly through the protocol
                $protocol->processMessage($message, $sessionId, ['request' => $request, 'response' => $response]);
                
                // Check if we received a response
                if ($this->pendingResponse && $this->pendingSessionId === $sessionId) {
                    $response->format = Response::FORMAT_JSON;
                    $response->data = $this->pendingResponse;
                } else {
                    // Fallback response
                    $response->format = Response::FORMAT_JSON;
                    $response->data = ['jsonrpc' => '2.0', 'id' => $body['id'] ?? null, 'result' => null];
                }
            } catch (\Exception $serverException) {
                // Fallback to event emission
                $this->pendingResponse = null;
                $this->pendingSessionId = null;
                
                $this->emit('message', [$message, $sessionId, ['request' => $request, 'response' => $response]]);
                
                if ($this->pendingResponse && $this->pendingSessionId === $sessionId) {
                    $response->format = Response::FORMAT_JSON;
                    $response->data = $this->pendingResponse;
                } else {
                    $response->format = Response::FORMAT_JSON;
                    $response->data = ['jsonrpc' => '2.0', 'id' => $body['id'] ?? null, 'result' => null];
                }
            }
            
            return $response;
        } catch (\Exception $e) {
            $response->format = Response::FORMAT_JSON;
            $response->data = [
                'jsonrpc' => '2.0',
                'id' => $body['id'] ?? null,
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error: ' . $e->getMessage(),
                ]
            ];
            return $response;
        }
    }

    /**
     * Handle GET requests - SSE streaming
     */
    public function handleGet(Request $request, Response $response): Response
    {
        $sessionId = $request->getQueryParam('sessionId');
        if (!$sessionId || !isset($this->sessions[$sessionId])) {
            throw new NotFoundHttpException('Session not found');
        }

        $this->currentSessionId = $sessionId;

        // Set SSE headers
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->headers->set('X-Accel-Buffering', 'no');

        $lastEventId = (int)$request->getQueryParam('lastEventId', 0);
        $session = &$this->sessions[$sessionId];

        $response->stream = function () use (&$session, $lastEventId) {
            // Send ready event
            yield "event: ready\n";
            yield "data: " . json_encode(['sessionId' => $this->currentSessionId]) . "\n\n";

            // Send any queued messages
            foreach ($session['messages'] as $index => $messageData) {
                if ($index > $lastEventId) {
                    yield "event: message\n";
                    yield "id: " . $index . "\n";
                    yield "data: " . json_encode($messageData['message']) . "\n\n";
                }
            }

            // Keep connection alive and send new messages
            $sentMessages = count($session['messages']);
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                // Check for new messages
                if (count($session['messages']) > $sentMessages) {
                    $newMessages = array_slice($session['messages'], $sentMessages);
                    foreach ($newMessages as $index => $messageData) {
                        $eventId = $sentMessages + $index;
                        yield "event: message\n";
                        yield "id: " . $eventId . "\n";
                        yield "data: " . json_encode($messageData['message']) . "\n\n";
                    }
                    $sentMessages = count($session['messages']);
                }

                // Send heartbeat
                yield "event: heartbeat\n";
                yield "data: " . json_encode(['timestamp' => time()]) . "\n\n";

                sleep(1);
            }
        };

        return $response;
    }

    /**
     * Handle DELETE requests - session cleanup
     */
    public function handleDelete(Request $request, Response $response): Response
    {
        $sessionId = $request->getQueryParam('sessionId');
        if ($sessionId && isset($this->sessions[$sessionId])) {
            $this->emit('client_disconnected', [$sessionId, 'Client requested disconnect']);
            unset($this->sessions[$sessionId]);
        }

        $response->format = Response::FORMAT_JSON;
        $response->data = ['success' => true];
        return $response;
    }

    /**
     * Initialize a new session
     */
    protected function initializeSession(string $sessionId): void
    {
        if (!isset($this->sessions[$sessionId])) {
            $this->sessions[$sessionId] = [
                'id' => $sessionId,
                'created_at' => time(),
                'messages' => [],
            ];
            
            $this->emit('client_connected', [$sessionId]);
        }
    }

    /**
     * Generate a unique session ID
     */
    protected function generateSessionId(): string
    {
        return 'craft_mcp_' . uniqid() . '_' . time();
    }

    /**
     * Get current session ID
     */
    public function getCurrentSessionId(): ?string
    {
        return $this->currentSessionId;
    }

    /**
     * Get all active sessions
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }

    /**
     * Check if transport is listening
     */
    public function isListening(): bool
    {
        return $this->listening;
    }

    /**
     * Clean up old sessions (garbage collection)
     */
    public function cleanupSessions(int $maxAge = 3600): void
    {
        $now = time();
        foreach ($this->sessions as $sessionId => $session) {
            if (($now - $session['created_at']) > $maxAge) {
                $this->emit('client_disconnected', [$sessionId, 'Session expired']);
                unset($this->sessions[$sessionId]);
            }
        }
    }
}