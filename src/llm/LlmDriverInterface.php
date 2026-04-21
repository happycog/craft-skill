<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

/**
 * Contract for LLM provider drivers.
 *
 * Each driver adapts a specific provider's API (Anthropic, OpenAI, etc.)
 * into a common streaming interface. The chat controller orchestrates the
 * agentic tool-use loop while drivers handle wire-format translation and
 * HTTP streaming.
 */
interface LlmDriverInterface
{
    /**
     * Stream a single chat completion turn.
     *
     * The driver MUST invoke $onEvent for every meaningful chunk so the
     * controller can forward it to the browser as an SSE frame.
     *
     * Callback event shapes:
     *   ['type' => 'text',       'content' => string]          — partial text
     *   ['type' => 'tool_start', 'id' => string, 'name' => string, 'input' => array]
     *
     * @param  array<int, array<string, mixed>>  $messages     Conversation in internal format
     * @param  array<int, array<string, mixed>>  $tools        Tool definitions (from ToolSchemaBuilder)
     * @param  string                            $systemPrompt System-level instruction
     * @param  callable(array<string, mixed>): void $onEvent   Streaming callback
     * @return array<string, mixed> Complete assistant message in internal format:
     *   ['role' => 'assistant', 'content' => string, 'toolCalls' => ?array]
     */
    public function streamChat(array $messages, array $tools, string $systemPrompt, callable $onEvent): array;
}
