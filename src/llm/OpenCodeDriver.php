<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

/**
 * OpenCode driver using the OpenCode server REST API.
 *
 * Connects to a locally-running OpenCode server (default 127.0.0.1:4096) and
 * uses whatever model the server is configured to use by default.
 *
 * Wire-format reference: https://opencode.ai/docs/server/
 *
 * Integration notes:
 *  - OpenCode is session-based. Each streamChat() call creates a short-lived
 *    session and replays the full conversation as a formatted prompt, since
 *    the chat controller owns conversation state.
 *  - OpenCode runs its own agentic loop with its own tools. External
 *    Craft MCP tool definitions are not forwarded — OpenCode responds with
 *    plain text (and any tool use it chooses internally is transparent).
 *  - Requests carry `?directory=<path>` so the OpenCode server scopes the
 *    session to the Craft project (sets projectID and working directory).
 */
final class OpenCodeDriver implements LlmDriverInterface
{
    private const DEFAULT_BASE_URL = 'http://127.0.0.1:4096';
    private const DEFAULT_USERNAME = 'opencode';
    private const LOG_CATEGORY     = 'craft-skill:opencode';

    private Client $client;

    /** Short id for correlating all log lines from a single streamChat() call. */
    private string $reqId = '';

    public function __construct(
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly string $password = '',
        private readonly string $username = self::DEFAULT_USERNAME,
        private readonly string $directory = '',
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    /** {@inheritDoc} */
    public function streamChat(array $messages, array $tools, string $systemPrompt, callable $onEvent): array
    {
        $this->reqId = bin2hex(random_bytes(4));
        $started = microtime(true);

        $this->log('streamChat:start', [
            'messageCount'      => count($messages),
            'toolCount'         => count($tools),
            'systemPromptChars' => strlen($systemPrompt),
            'lastUserPreview'   => $this->previewLastUserMessage($messages),
            'baseUrl'           => $this->baseUrl,
            'directory'         => $this->directory,
        ]);

        // OpenCode's REST API is not streaming — a single POST blocks until
        // the entire turn is done. For long turns that's minutes of silence
        // on the wire, which any reverse proxy (or the browser's fetch) will
        // kill. The heartbeat keeps the SSE connection warm.
        $heartbeat = new HttpHeartbeat();
        $tickCount = 0;
        $progress = function (int $dlTotal, int $dlNow, int $ulTotal, int $ulNow) use ($heartbeat, $onEvent, &$tickCount): void {
            $tickCount++;
            $heartbeat->tick($onEvent);
        };

        try {
            $sessionId = $this->createSession($progress);
            $this->log('streamChat:session-created', ['sessionId' => $sessionId]);

            $prompt = $this->buildPrompt($messages);
            $this->log('streamChat:prompt-built', [
                'promptChars'   => strlen($prompt),
                'promptPreview' => $this->preview($prompt, 400),
            ]);

            $body = [
                'system' => $systemPrompt,
                'parts'  => [
                    ['type' => 'text', 'text' => $prompt],
                ],
            ];

            $postStarted = microtime(true);
            $this->log('streamChat:post-start', [
                'sessionId'   => $sessionId,
                'bodyBytes'   => strlen((string) json_encode($body)),
            ]);

            $response = $this->postMessage($sessionId, $body, $progress);

            $this->log('streamChat:post-returned', [
                'sessionId'      => $sessionId,
                'durationMs'     => (int) round((microtime(true) - $postStarted) * 1000),
                'progressTicks'  => $tickCount,
                'responseKeys'   => array_keys($response),
                'partCount'      => is_array($response['parts'] ?? null) ? count($response['parts']) : 0,
            ]);

            $result = $this->handleResponse($response, $onEvent);

            $this->log('streamChat:done', [
                'totalDurationMs' => (int) round((microtime(true) - $started) * 1000),
                'replyChars'      => strlen(is_string($result['content'] ?? null) ? $result['content'] : ''),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->log('streamChat:error', [
                'exception'       => $e::class,
                'message'         => $e->getMessage(),
                'progressTicks'   => $tickCount,
                'totalDurationMs' => (int) round((microtime(true) - $started) * 1000),
            ]);

            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Conversation flattening (internal history → single prompt string)
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>> $messages
     */
    private function buildPrompt(array $messages): string
    {
        // Common case: a single user turn — pass it through verbatim so the
        // prompt reads naturally instead of being wrapped in a transcript.
        if (count($messages) === 1 && ($messages[0]['role'] ?? '') === 'user') {
            $content = $messages[0]['content'] ?? '';
            return is_string($content) ? $content : '';
        }

        $lines = ['Conversation history:'];

        foreach ($messages as $msg) {
            $role    = is_string($msg['role'] ?? null) ? $msg['role'] : '';
            $content = is_string($msg['content'] ?? null) ? $msg['content'] : '';

            switch ($role) {
                case 'user':
                    $lines[] = '';
                    $lines[] = 'User: ' . $content;
                    break;

                case 'assistant':
                    $lines[] = '';
                    $lines[] = 'Assistant: ' . ($content !== '' ? $content : '(no text)');

                    /** @var array<int, array<string, mixed>> $toolCalls */
                    $toolCalls = is_array($msg['toolCalls'] ?? null) ? $msg['toolCalls'] : [];

                    foreach ($toolCalls as $tc) {
                        $name  = is_string($tc['name'] ?? null) ? $tc['name'] : '';
                        $input = is_array($tc['input'] ?? null) ? $tc['input'] : [];
                        $json  = json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        $lines[] = '  [called tool ' . $name . ' with ' . ($json !== false ? $json : '{}') . ']';
                    }
                    break;

                case 'tool':
                    $name = is_string($msg['name'] ?? null) ? $msg['name'] : '';
                    $lines[] = '  [tool ' . $name . ' returned]: ' . $content;
                    break;
            }
        }

        $lines[] = '';
        $lines[] = 'Please respond to the most recent user message above.';

        return implode("\n", $lines);
    }

    // ------------------------------------------------------------------
    // Response parsing
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $response
     * @param  callable(array<string, mixed>): void $onEvent
     * @return array<string, mixed>
     */
    private function handleResponse(array $response, callable $onEvent): array
    {
        /** @var array<int, array<string, mixed>> $parts */
        $parts = is_array($response['parts'] ?? null) ? $response['parts'] : [];

        $partTypes = array_map(
            static fn ($p): string => is_array($p) && is_string($p['type'] ?? null) ? $p['type'] : '?',
            $parts,
        );

        $this->log('handleResponse:parts', [
            'partCount' => count($parts),
            'partTypes' => array_count_values($partTypes),
        ]);

        $textContent = '';
        $emittedChunks = 0;
        $skippedEmpty = 0;

        foreach ($parts as $index => $part) {
            if (! is_array($part)) {
                continue;
            }

            $type = is_string($part['type'] ?? null) ? $part['type'] : '';

            if ($type === 'text') {
                $chunk = is_string($part['text'] ?? null) ? $part['text'] : '';
                if ($chunk === '') {
                    $skippedEmpty++;
                    continue;
                }
                $textContent .= $chunk;
                $emittedChunks++;
                $onEvent(['type' => 'text', 'content' => $chunk]);
                continue;
            }

            // Surface anything non-text so we can see when OpenCode returns
            // e.g. tool-call parts or error parts instead of a reply.
            $this->log('handleResponse:non-text-part', [
                'index'   => $index,
                'type'    => $type,
                'preview' => $this->preview((string) json_encode($part), 400),
            ]);
        }

        if ($emittedChunks === 0) {
            $this->log('handleResponse:no-text-emitted', [
                'partCount'    => count($parts),
                'skippedEmpty' => $skippedEmpty,
                'responseKeys' => array_keys($response),
                'rawPreview'   => $this->preview((string) json_encode($response), 800),
            ]);
        }

        return [
            'role'    => 'assistant',
            'content' => $textContent,
        ];
    }

    // ------------------------------------------------------------------
    // HTTP
    // ------------------------------------------------------------------

    /**
     * @param  ?callable(int, int, int, int): void $progress
     */
    private function createSession(?callable $progress = null): string
    {
        // Deliberately send no title: OpenCode only auto-generates a summary
        // title (via its background title agent) when the initial title
        // matches its default pattern ("New session - <ISO timestamp>"). Any
        // custom title we supply would suppress that and leave the session
        // stuck with our placeholder in the OpenCode UI.
        $decoded = $this->request('POST', '/session', new \stdClass(), $progress);

        $id = is_string($decoded['id'] ?? null) ? $decoded['id'] : '';

        if ($id === '') {
            throw new \RuntimeException('OpenCode server returned a session without an id.');
        }

        return $id;
    }

    /**
     * @param  array<string, mixed> $body
     * @param  ?callable(int, int, int, int): void $progress
     * @return array<string, mixed>
     */
    private function postMessage(string $sessionId, array $body, ?callable $progress = null): array
    {
        return $this->request('POST', '/session/' . rawurlencode($sessionId) . '/message', $body, $progress);
    }

    /**
     * @param  array<string, mixed>|\stdClass $body
     * @param  ?callable(int, int, int, int): void $progress
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array|\stdClass $body, ?callable $progress = null): array
    {
        if ($this->directory !== '') {
            $separator = str_contains($path, '?') ? '&' : '?';
            $path .= $separator . 'directory=' . rawurlencode($this->directory);
        }

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json'    => $body,
            'timeout' => 300,
        ];

        if ($progress !== null) {
            $options['progress'] = $progress;
        }

        if ($this->password !== '') {
            $options['auth'] = [$this->username, $this->password];
        }

        $url = rtrim($this->baseUrl, '/') . $path;
        $startedAt = microtime(true);

        $this->log('http:start', [
            'method' => $method,
            'path'   => $path,
        ]);

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($errorBody, true);
            $message = 'OpenCode API error (' . $e->getResponse()->getStatusCode() . ')';

            if (is_array($decoded)) {
                $errors = is_array($decoded['errors'] ?? null) ? $decoded['errors'] : [];
                $first  = $errors[0] ?? null;
                if (is_array($first) && is_string($first['message'] ?? null) && $first['message'] !== '') {
                    $message = $first['message'];
                } elseif (is_string($decoded['message'] ?? null) && $decoded['message'] !== '') {
                    $message = $decoded['message'];
                }
            }

            $this->log('http:client-error', [
                'method'     => $method,
                'path'       => $path,
                'status'     => $e->getResponse()->getStatusCode(),
                'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                'body'       => $this->preview($errorBody, 600),
            ]);

            throw new \RuntimeException($message);
        } catch (ServerException $e) {
            $this->log('http:server-error', [
                'method'     => $method,
                'path'       => $path,
                'status'     => $e->getResponse()->getStatusCode(),
                'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                'body'       => $this->preview($e->getResponse()->getBody()->getContents(), 600),
            ]);
            throw new \RuntimeException('OpenCode server error — please try again.');
        } catch (ConnectException $e) {
            $this->log('http:connect-error', [
                'method'     => $method,
                'path'       => $path,
                'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
                'message'    => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to connect to the OpenCode server at ' . $this->baseUrl . '.');
        }

        $raw = (string) $response->getBody();

        $this->log('http:ok', [
            'method'     => $method,
            'path'       => $path,
            'status'     => $response->getStatusCode(),
            'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
            'bodyBytes'  => strlen($raw),
        ]);

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $this->log('http:invalid-json', [
                'method'  => $method,
                'path'    => $path,
                'preview' => $this->preview($raw, 600),
            ]);
            throw new \RuntimeException('OpenCode server returned an invalid JSON response.');
        }

        return $decoded;
    }

    // ------------------------------------------------------------------
    // Logging helpers
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $context
     */
    private function log(string $event, array $context = []): void
    {
        $payload = ['req' => $this->reqId, 'event' => $event] + $context;
        Craft::info((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), self::LOG_CATEGORY);
    }

    private function preview(string $text, int $max): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }
        if (mb_strlen($trimmed) <= $max) {
            return $trimmed;
        }
        return mb_substr($trimmed, 0, $max) . '…';
    }

    /**
     * @param  array<int, array<string, mixed>> $messages
     */
    private function previewLastUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $msg = $messages[$i];
            if (is_array($msg) && ($msg['role'] ?? null) === 'user' && is_string($msg['content'] ?? null)) {
                return $this->preview($msg['content'], 200);
            }
        }
        return '';
    }
}
