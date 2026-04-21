<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

/**
 * OpenAI-compatible driver using the Chat Completions API with streaming.
 *
 * Works with OpenAI, Azure OpenAI, and any provider that speaks the same
 * wire format (Together, Groq, Ollama, LM Studio, etc.).  Set `$baseUrl`
 * to point at the desired endpoint.
 */
final class OpenAiDriver implements LlmDriverInterface
{
    private const DEFAULT_BASE_URL = 'https://api.openai.com/v1';
    private const DEFAULT_MODEL    = 'gpt-4o';

    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    /** {@inheritDoc} */
    public function streamChat(array $messages, array $tools, string $systemPrompt, callable $onEvent): array
    {
        $openAiMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$this->convertMessages($messages),
        ];

        $body = [
            'model'    => $this->model,
            'stream'   => true,
            'messages' => $openAiMessages,
        ];

        if ($tools !== []) {
            $body['tools'] = $this->convertTools($tools);
        }

        return $this->streamRequest($body, $onEvent);
    }

    // ------------------------------------------------------------------
    // Message format conversion  (internal → OpenAI)
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>> $messages
     * @return array<int, array<string, mixed>>
     */
    private function convertMessages(array $messages): array
    {
        /** @var array<int, array<string, mixed>> $out */
        $out = [];

        foreach ($messages as $msg) {
            $role = is_string($msg['role'] ?? null) ? $msg['role'] : '';

            switch ($role) {
                case 'user':
                    $out[] = ['role' => 'user', 'content' => is_string($msg['content'] ?? null) ? $msg['content'] : ''];
                    break;

                case 'assistant':
                    /** @var array<string, mixed> $m */
                    $m = ['role' => 'assistant'];

                    $text = is_string($msg['content'] ?? null) ? $msg['content'] : '';
                    if ($text !== '') {
                        $m['content'] = $text;
                    }

                    /** @var array<int, array<string, mixed>> $toolCalls */
                    $toolCalls = is_array($msg['toolCalls'] ?? null) ? $msg['toolCalls'] : [];

                    if ($toolCalls !== []) {
                        /** @var array<int, array<string, mixed>> $converted */
                        $converted = [];

                        foreach ($toolCalls as $tc) {
                            $converted[] = [
                                'id'       => is_string($tc['id'] ?? null) ? $tc['id'] : '',
                                'type'     => 'function',
                                'function' => [
                                    'name'      => is_string($tc['name'] ?? null) ? $tc['name'] : '',
                                    'arguments' => json_encode(is_array($tc['input'] ?? null) ? $tc['input'] : [], JSON_THROW_ON_ERROR),
                                ],
                            ];
                        }

                        $m['tool_calls'] = $converted;
                    }

                    $out[] = $m;
                    break;

                case 'tool':
                    $out[] = [
                        'role'         => 'tool',
                        'tool_call_id' => is_string($msg['toolCallId'] ?? null) ? $msg['toolCallId'] : '',
                        'content'      => is_string($msg['content'] ?? null) ? $msg['content'] : '',
                    ];
                    break;
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>> $tools
     * @return array<int, array<string, mixed>>
     */
    private function convertTools(array $tools): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = [];

        foreach ($tools as $tool) {
            $result[] = [
                'type'     => 'function',
                'function' => [
                    'name'        => is_string($tool['name'] ?? null) ? $tool['name'] : '',
                    'description' => is_string($tool['description'] ?? null) ? $tool['description'] : '',
                    'parameters'  => $tool['parameters'] ?? (object) [],
                ],
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Streaming HTTP
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $body
     * @param  callable(array<string, mixed>): void $onEvent
     * @return array<string, mixed>
     */
    private function streamRequest(array $body, callable $onEvent): array
    {
        try {
            $response = $this->client->post(rtrim($this->baseUrl, '/') . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'text/event-stream',
                ],
                'json'    => $body,
                'stream'  => true,
                'timeout' => 300,
            ]);
        } catch (ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($errorBody, true) ?? [];
            $error   = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
            $message = is_string($error['message'] ?? null)
                ? $error['message']
                : 'OpenAI API error (' . $e->getResponse()->getStatusCode() . ')';
            throw new \RuntimeException($message);
        } catch (ServerException) {
            throw new \RuntimeException('OpenAI-compatible API server error — please try again.');
        } catch (ConnectException) {
            throw new \RuntimeException('Failed to connect to the OpenAI-compatible API.');
        }

        $stream = $response->getBody();
        $buffer = '';

        $textContent = '';
        /** @var array<int, array{id: string, name: string, arguments: string}> */
        $toolCalls = [];

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);

            if ($chunk === '') {
                break;
            }

            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || $line === 'data: [DONE]') {
                    continue;
                }

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                /** @var array<string, mixed>|null $data */
                $data = json_decode(substr($line, 6), true);

                if (! is_array($data)) {
                    continue;
                }

                $rawChoices = $data['choices'] ?? [];
                $choicesList = is_array($rawChoices) ? $rawChoices : [];
                $firstChoice = $choicesList[0] ?? null;
                /** @var array<string, mixed> $choice */
                $choice = is_array($firstChoice) ? $firstChoice : [];

                if ($choice === []) {
                    continue;
                }

                /** @var array<string, mixed> $delta */
                $delta = is_array($choice['delta'] ?? null) ? $choice['delta'] : [];

                // Text content
                if (isset($delta['content']) && is_string($delta['content'])) {
                    $textContent .= $delta['content'];
                    $onEvent(['type' => 'text', 'content' => $delta['content']]);
                }

                // Tool-call deltas — accumulated by index
                if (isset($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                    /** @var array<int, array<string, mixed>> $deltaToolCalls */
                    $deltaToolCalls = $delta['tool_calls'];

                    foreach ($deltaToolCalls as $tc) {
                        if (! is_array($tc)) {
                            continue;
                        }

                        $index = is_int($tc['index'] ?? null) ? $tc['index'] : 0;

                        if (isset($tc['id']) && is_string($tc['id'])) {
                            /** @var array<string, mixed> $fn */
                            $fn = is_array($tc['function'] ?? null) ? $tc['function'] : [];
                            $toolCalls[$index] = [
                                'id'        => $tc['id'],
                                'name'      => is_string($fn['name'] ?? null) ? $fn['name'] : '',
                                'arguments' => '',
                            ];
                        }

                        if (isset($toolCalls[$index])) {
                            /** @var array<string, mixed> $fn */
                            $fn = is_array($tc['function'] ?? null) ? $tc['function'] : [];

                            if (is_string($fn['name'] ?? null) && $fn['name'] !== '') {
                                $toolCalls[$index]['name'] = $fn['name'];
                            }

                            if (is_string($fn['arguments'] ?? null)) {
                                $toolCalls[$index]['arguments'] .= $fn['arguments'];
                            }
                        }
                    }
                }

                // Finish — emit accumulated tool_starts
                $finishReason = is_string($choice['finish_reason'] ?? null) ? $choice['finish_reason'] : null;

                if ($finishReason === 'tool_calls') {
                    foreach ($toolCalls as $tc) {
                        $decoded = json_decode($tc['arguments'], true);
                        /** @var array<string, mixed> $input */
                        $input = is_array($decoded) ? $decoded : [];
                        $onEvent([
                            'type'  => 'tool_start',
                            'id'    => $tc['id'],
                            'name'  => $tc['name'],
                            'input' => $input,
                        ]);
                    }
                }
            }
        }

        // Build internal-format assistant message
        $assistantMessage = [
            'role'    => 'assistant',
            'content' => $textContent,
        ];

        if ($toolCalls !== []) {
            /** @var array<int, array{id: string, name: string, input: array<string, mixed>}> $normalized */
            $normalized = [];

            foreach ($toolCalls as $tc) {
                $decoded = json_decode($tc['arguments'], true);
                /** @var array<string, mixed> $input */
                $input = is_array($decoded) ? $decoded : [];
                $normalized[] = [
                    'id'    => $tc['id'],
                    'name'  => $tc['name'],
                    'input' => $input,
                ];
            }

            $assistantMessage['toolCalls'] = $normalized;
        }

        return $assistantMessage;
    }
}
