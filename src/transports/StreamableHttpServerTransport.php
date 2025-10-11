<?php

namespace happycog\craftmcp\transports;

use Craft;
use craft\web\Request;
use craft\web\Response;
use PhpMcp\Server\Contracts\ServerTransportInterface;
use PhpMcp\Server\Contracts\SessionHandlerInterface;
use PhpMcp\Schema\JsonRpc\Message;
use PhpMcp\Schema\JsonRpc\Parser;
use Evenement\EventEmitterTrait;
use React\Promise\PromiseInterface;
use React\Promise\Promise;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use happycog\craftmcp\session\CraftSessionHandler;

class StreamableHttpServerTransport implements ServerTransportInterface
{
    use EventEmitterTrait;

    protected SessionHandlerInterface $sessionHandler;
    
    /**
     * @var array<string, array{id: string, created_at: int, messages: array<int, array{message: Message, context: array<string, mixed>, timestamp: int}>}>
     */
    protected array $sessions = []; // Keep for SSE message queuing
    protected ?string $currentSessionId = null;
    protected bool $listening = false;

    public function __construct(?SessionHandlerInterface $sessionHandler = null)
    {
        $this->sessionHandler = $sessionHandler ?? new CraftSessionHandler();
    }

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

    /** @var array<string, mixed>|null */
    protected ?array $pendingResponse = null;
    protected ?string $pendingSessionId = null;

    /**
     * @param array<string, mixed> $context
     * @return PromiseInterface<void>
     */
    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface
    {
        return new Promise(function ($resolve) use ($message, $sessionId, $context) {
            if (!isset($this->sessions[$sessionId])) {
                throw new \Exception("Session not found: $sessionId");
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

            // Ensure messageData is array type for pendingResponse
            if (is_array($messageData)) {
                $this->pendingResponse = $messageData;
            } else {
                // Fallback to Message object as array
                $this->pendingResponse = ['message' => $messageData];
            }
            $this->pendingSessionId = $sessionId;

            /** @phpstan-ignore-next-line */
            $resolve();
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

        // Check if this is an initialize request
        $rawBody = $request->getRawBody();
        $body = $request->getBodyParams();
        
        // If we don't have parsed body params, try to parse the raw body
        if (empty($body)) {
            $decoded = json_decode($rawBody, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($body) || !isset($body['method'])) {
            throw new BadRequestHttpException('Invalid JSON-RPC request');
        }

        $method = $body['method'];
        if (!is_string($method)) {
            throw new BadRequestHttpException('Invalid JSON-RPC method');
        }

        $isInitializeRequest = ($method === 'initialize');
        $sessionId = null;

        if ($isInitializeRequest) {
            // For initialize requests, generate a new session ID
            if (!($this->sessionHandler instanceof CraftSessionHandler)) {
                throw new \RuntimeException('Session handler must be CraftSessionHandler');
            }
            $sessionId = $this->sessionHandler->generateSessionId();
            $this->currentSessionId = $sessionId;
            $this->initializeSession($sessionId, true); // true = emit client_connected
        } else {
            // For other requests, session ID is required (check header first, then query param)
            $sessionId = $request->getHeaders()->get('Mcp-Session-Id') ?: $request->getQueryParam('sessionId');
            if (!is_string($sessionId)) {
                throw new BadRequestHttpException('Mcp-Session-Id header or sessionId parameter required for non-initialize requests');
            }
            $this->currentSessionId = $sessionId;
            $this->initializeSession($sessionId, false); // false = don't emit client_connected
        }

        try {
            // Parse the JSON-RPC message - Parser expects JSON string
            /** @phpstan-ignore-next-line */
            $jsonString = is_string($rawBody) ? $rawBody : json_encode($body);
            /** @phpstan-ignore-next-line */
            if ($jsonString === false) {
                throw new BadRequestHttpException('Failed to encode body as JSON');
            }
            $message = Parser::parse($jsonString);
            
            // Clear any pending response
            $this->pendingResponse = null;
            $this->pendingSessionId = null;
            
            // Emit the message event to let the protocol handle it
            $this->emit('message', [$message, $sessionId, ['request' => $request, 'response' => $response]]);
            
            // Check if we received a response
            /** @phpstan-ignore-next-line */
            if ($this->pendingResponse !== null && $this->pendingSessionId === $sessionId) {
                $response->format = Response::FORMAT_JSON;
                $response->data = $this->pendingResponse;
                
                // For initialize requests, include the session ID in the response headers
                if ($isInitializeRequest && is_string($sessionId)) {
                    $response->headers->set('Mcp-Session-Id', $sessionId);
                }
            } else {
                // Fallback response
                $response->format = Response::FORMAT_JSON;
                $id = is_array($body) && isset($body['id']) ? $body['id'] : null;
                $response->data = ['jsonrpc' => '2.0', 'id' => $id, 'result' => null];
                
                // For initialize requests, include the session ID in the response headers
                if ($isInitializeRequest && is_string($sessionId)) {
                    $response->headers->set('Mcp-Session-Id', $sessionId);
                }
            }
            
            return $response;
        } catch (\Exception $e) {
            $response->format = Response::FORMAT_JSON;
            $id = is_array($body) && isset($body['id']) ? $body['id'] : null;
            $response->data = [
                'jsonrpc' => '2.0',
                'id' => $id,
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
        if (!is_string($sessionId) || !isset($this->sessions[$sessionId])) {
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

        $lastEventId = is_numeric($request->getQueryParam('lastEventId', 0)) ? (int)$request->getQueryParam('lastEventId', 0) : 0;
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
                $currentMessageCount = count($session['messages']);
                if ($currentMessageCount > $sentMessages) {
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
        if (is_string($sessionId)) {
            // Remove from persistent storage
            $this->sessionHandler->destroy($sessionId);
            
            // Remove from memory
            if (isset($this->sessions[$sessionId])) {
                unset($this->sessions[$sessionId]);
            }
            
            $this->emit('client_disconnected', [$sessionId, 'Client requested disconnect']);
        }

        $response->format = Response::FORMAT_JSON;
        $response->data = ['success' => true];
        return $response;
    }

    /**
     * Initialize a new session
     */
    protected function initializeSession(string $sessionId, bool $emitClientConnected = false): void
    {
        // Initialize in-memory session for SSE message queuing if not exists
        if (!isset($this->sessions[$sessionId])) {
            $this->sessions[$sessionId] = [
                'id' => $sessionId,
                'created_at' => time(),
                'messages' => [],
            ];
        }
        
        // Only emit client_connected for initialize requests to create new MCP sessions
        if ($emitClientConnected) {
            $this->emit('client_connected', [$sessionId]);
        }
    }

    /**
     * Check if a session exists in persistent storage
     */
    public function sessionExists(string $sessionId): bool
    {
        return $this->sessionHandler->read($sessionId) !== false;
    }

    /**
     * Get session data from persistent storage
     * 
     * @return array<string, mixed>|false
     */
    public function getSessionData(string $sessionId): array|false
    {
        $data = $this->sessionHandler->read($sessionId);
        if ($data === false) {
            return false;
        }
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : false;
    }

    /**
     * Update session data in persistent storage
     * 
     * @param array<string, mixed> $data
     */
    public function updateSessionData(string $sessionId, array $data): bool
    {
        $jsonData = json_encode($data);
        return $jsonData !== false && $this->sessionHandler->write($sessionId, $jsonData);
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
     * 
     * @return array<string, mixed>
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
        // Clean up persistent sessions
        $deletedSessions = $this->sessionHandler->gc($maxAge);
        
        // Clean up in-memory sessions
        $now = time();
        foreach ($this->sessions as $sessionId => $session) {
            if (($now - $session['created_at']) > $maxAge) {
                $this->emit('client_disconnected', [$sessionId, 'Session expired']);
                unset($this->sessions[$sessionId]);
            }
        }
        
        // Also clean up any sessions that were deleted from persistent storage
        foreach ($deletedSessions as $sessionId) {
            if (isset($this->sessions[$sessionId])) {
                $this->emit('client_disconnected', [$sessionId, 'Session expired']);
                unset($this->sessions[$sessionId]);
            }
        }
    }
}
