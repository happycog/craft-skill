<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

/**
 * Claude driver using the Anthropic Messages API with streaming.
 *
 * Wire format reference: https://docs.anthropic.com/en/api/messages-streaming
 */
final class AnthropicDriver implements LlmDriverInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const DEFAULT_MODEL = 'claude-sonnet-4-20250514';
    private const MAX_TOKENS = 8192;

    private Client $client;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    /** {@inheritDoc} */
    public function streamChat(array $messages, array $tools, string $systemPrompt, callable $onEvent): array
    {
        $body = [
            'model'      => $this->model,
            'max_tokens' => self::MAX_TOKENS,
            'stream'     => true,
            'system'     => $systemPrompt,
            'messages'   => $this->convertMessages($messages),
        ];

        if ($tools !== []) {
            $body['tools'] = $this->convertTools($tools);
        }

        return $this->streamRequest($body, $onEvent);
    }

    // ------------------------------------------------------------------
    // Message format conversion  (internal → Anthropic)
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
                    /** @var array<int, array<string, mixed>> $content */
                    $content = [];

                    $text = is_string($msg['content'] ?? null) ? $msg['content'] : '';
                    if ($text !== '') {
                        $content[] = ['type' => 'text', 'text' => $text];
                    }

                    /** @var array<int, array<string, mixed>> $toolCalls */
                    $toolCalls = is_array($msg['toolCalls'] ?? null) ? $msg['toolCalls'] : [];

                    foreach ($toolCalls as $tc) {
                        $content[] = [
                            'type'  => 'tool_use',
                            'id'    => is_string($tc['id'] ?? null) ? $tc['id'] : '',
                            'name'  => is_string($tc['name'] ?? null) ? $tc['name'] : '',
                            'input' => (object) (is_array($tc['input'] ?? null) ? $tc['input'] : []),
                        ];
                    }

                    $out[] = ['role' => 'assistant', 'content' => $content];
                    break;

                case 'tool':
                    $block = [
                        'type'        => 'tool_result',
                        'tool_use_id' => is_string($msg['toolCallId'] ?? null) ? $msg['toolCallId'] : '',
                        'content'     => is_string($msg['content'] ?? null) ? $msg['content'] : '',
                    ];

                    $lastKey = array_key_last($out);
                    $last    = $lastKey !== null ? $out[$lastKey] : null;

                    if (
                        $last !== null
                        && ($last['role'] ?? '') === 'user'
                        && is_array($last['content'] ?? null)
                    ) {
                        /** @var array<int, array<string, mixed>> $existingContent */
                        $existingContent = $last['content'];
                        $firstBlock      = $existingContent[0] ?? [];
                        if (is_array($firstBlock) && ($firstBlock['type'] ?? '') === 'tool_result') {
                            $existingContent[] = $block;
                            $out[$lastKey]     = ['role' => 'user', 'content' => $existingContent];
                            break;
                        }
                    }

                    $out[] = ['role' => 'user', 'content' => [$block]];
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
                'name'         => is_string($tool['name'] ?? null) ? $tool['name'] : '',
                'description'  => is_string($tool['description'] ?? null) ? $tool['description'] : '',
                'input_schema' => $tool['parameters'] ?? (object) [],
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Streaming HTTP + SSE parsing
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $body
     * @param  callable(array<string, mixed>): void $onEvent
     * @return array<string, mixed>
     */
    private function streamRequest(array $body, callable $onEvent): array
    {
        try {
            $response = $this->client->post(self::API_URL, [
                'headers' => [
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version'  => self::API_VERSION,
                    'content-type'       => 'application/json',
                    'accept'             => 'text/event-stream',
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
                : 'Anthropic API error (' . $e->getResponse()->getStatusCode() . ')';
            throw new \RuntimeException($message);
        } catch (ServerException) {
            throw new \RuntimeException('Anthropic API server error — please try again.');
        } catch (ConnectException) {
            throw new \RuntimeException('Failed to connect to the Anthropic API.');
        }

        $stream = $response->getBody();
        $buffer = '';

        $textContent       = '';
        /** @var array<int, array{id: string, name: string, input: array<string, mixed>}> */
        $toolCalls         = [];
        $currentBlockIndex = -1;
        $currentBlockType  = '';
        $currentToolJson   = '';

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);

            if ($chunk === '') {
                break;
            }

            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $rawEvent = substr($buffer, 0, $pos);
                $buffer   = substr($buffer, $pos + 2);

                $parsed = $this->parseSseFrame($rawEvent);

                if ($parsed === null) {
                    continue;
                }

                $data = $parsed['data'];

                if ($data === null) {
                    continue;
                }

                $type = is_string($data['type'] ?? null) ? $data['type'] : '';

                switch ($type) {
                    case 'content_block_start':
                        /** @var array<string, mixed> $block */
                        $block             = is_array($data['content_block'] ?? null) ? $data['content_block'] : [];
                        $currentBlockIndex = is_int($data['index'] ?? null) ? $data['index'] : 0;
                        $currentBlockType  = is_string($block['type'] ?? null) ? $block['type'] : '';

                        if ($currentBlockType === 'tool_use') {
                            $currentToolJson = '';
                            $toolCalls[$currentBlockIndex] = [
                                'id'    => is_string($block['id'] ?? null) ? $block['id'] : '',
                                'name'  => is_string($block['name'] ?? null) ? $block['name'] : '',
                                'input' => [],
                            ];
                        }
                        break;

                    case 'content_block_delta':
                        /** @var array<string, mixed> $delta */
                        $delta     = is_array($data['delta'] ?? null) ? $data['delta'] : [];
                        $deltaType = is_string($delta['type'] ?? null) ? $delta['type'] : '';

                        if ($deltaType === 'text_delta') {
                            $text = is_string($delta['text'] ?? null) ? $delta['text'] : '';
                            $textContent .= $text;
                            $onEvent(['type' => 'text', 'content' => $text]);
                        } elseif ($deltaType === 'input_json_delta') {
                            $currentToolJson .= is_string($delta['partial_json'] ?? null) ? $delta['partial_json'] : '';
                        }
                        break;

                    case 'content_block_stop':
                        if ($currentBlockType === 'tool_use' && isset($toolCalls[$currentBlockIndex])) {
                            $decoded = json_decode($currentToolJson, true);
                            /** @var array<string, mixed> $input */
                            $input = is_array($decoded) ? $decoded : [];
                            $toolCalls[$currentBlockIndex]['input'] = $input;

                            $onEvent([
                                'type'  => 'tool_start',
                                'id'    => $toolCalls[$currentBlockIndex]['id'],
                                'name'  => $toolCalls[$currentBlockIndex]['name'],
                                'input' => $toolCalls[$currentBlockIndex]['input'],
                            ]);
                        }

                        $currentBlockType = '';
                        $currentToolJson  = '';
                        break;
                }
            }
        }

        $assistantMessage = [
            'role'    => 'assistant',
            'content' => $textContent,
        ];

        if ($toolCalls !== []) {
            $assistantMessage['toolCalls'] = array_values($toolCalls);
        }

        return $assistantMessage;
    }

    /**
     * Parse a single SSE frame into event type + decoded data.
     *
     * @return array{event: ?string, data: ?array<string, mixed>}|null
     */
    private function parseSseFrame(string $raw): ?array
    {
        $eventType = null;
        /** @var array<int, string> $dataLines */
        $dataLines = [];

        foreach (explode("\n", $raw) as $line) {
            if (str_starts_with($line, 'event: ')) {
                $eventType = substr($line, 7);
            } elseif (str_starts_with($line, 'data: ')) {
                $dataLines[] = substr($line, 6);
            }
        }

        if ($dataLines === []) {
            return null;
        }

        $json = implode('', $dataLines);
        /** @var array<string, mixed>|null $data */
        $data = json_decode($json, true);

        return [
            'event' => $eventType,
            'data'  => is_array($data) ? $data : null,
        ];
    }
}
