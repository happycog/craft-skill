<?php

declare(strict_types=1);

namespace happycog\craftmcp\controllers;

use Craft;
use craft\web\Controller as CraftController;
use happycog\craftmcp\llm\LlmManager;
use happycog\craftmcp\llm\ToolSchemaBuilder;
use yii\web\Response;

/**
 * SSE streaming endpoint for the embedded AI chat.
 *
 * The frontend POSTs the full conversation history + a new user message.
 * This controller runs an agentic loop — calling the LLM, executing any
 * requested tools, feeding results back — and streams every event to the
 * browser as a Server-Sent Event so the UI updates in real time.
 */
class ChatController extends CraftController
{
    /**
     * Require CP authentication but skip CSRF for the streaming POST.
     * The endpoint is only reachable by logged-in control-panel users.
     */
    public $enableCsrfValidation = false;

    /** Maximum number of tool-use loop iterations to prevent runaway agents. */
    private const MAX_LOOP_ITERATIONS = 25;

    /**
     * POST /actions/skills/chat/stream
     *
     * Body (JSON):
     *   messages  — prior conversation in internal format
     *   message   — the new user text
     */
    public function actionStream(): Response|null
    {
        // Give the agentic loop generous room to finish.
        set_time_limit(300);

        $rawBody = file_get_contents('php://input') ?: '{}';
        /** @var array<string, mixed> $input */
        $input   = json_decode($rawBody, true) ?? [];

        $newText = is_string($input['message'] ?? null) ? trim($input['message']) : '';
        $currentUrl = is_string($input['currentUrl'] ?? null) ? trim($input['currentUrl']) : '';

        /** @var array<int, array<string, mixed>> $history */
        $history = is_array($input['messages'] ?? null) ? $input['messages'] : [];

        if ($newText === '') {
            return $this->asJson(['error' => 'Message is required.']);
        }

        /** @var LlmManager $llm */
        $llm = Craft::$container->get(LlmManager::class);

        if (! $llm->isConfigured()) {
            return $this->asJson(['error' => 'LLM provider is not configured. Copy stubs/config/ai.php to your project\'s config/ai.php and add your API key.']);
        }

        // ── Prepare SSE transport ────────────────────────────────────
        $this->beginSse();

        // ── Build conversation ───────────────────────────────────────
        $messages   = $history;
        $messages[] = ['role' => 'user', 'content' => $newText];

        $schemaBuilder = new ToolSchemaBuilder();
        $revealedTools = [];
        $toolSearch    = $schemaBuilder->getTool(ToolSchemaBuilder::TOOL_SEARCH, compact: true);
        $tools         = $toolSearch === null ? [] : [$toolSearch];
        $systemPrompt  = $llm->buildSystemPrompt($currentUrl);

        $driver        = $llm->driver();

        // ── Agentic loop ─────────────────────────────────────────────
        /** @var array<int, array<string, mixed>> $newMessages */
        $newMessages = [];
        $iterations  = 0;

        do {
            $iterations++;
            $turnId = 'turn_' . bin2hex(random_bytes(8));
            $this->sendSseEvent('turn', ['id' => $turnId]);

            try {
                $assistantMessage = $driver->streamChat(
                    $messages,
                    array_values($tools),
                    $systemPrompt,
                    function (array $event): void {
                        $eventType = is_string($event['type'] ?? null) ? $event['type'] : 'unknown';
                        $this->sendSseEvent($eventType, $event);
                    },
                );
            } catch (\Throwable $e) {
                $this->sendSseEvent('error', ['message' => $e->getMessage()]);
                break;
            }

            $messages[]    = $assistantMessage;
            $newMessages[] = $assistantMessage;

            /** @var array<int, array{id: string, name: string, input: array<string, mixed>}> $toolCalls */
            $toolCalls    = is_array($assistantMessage['toolCalls'] ?? null) ? $assistantMessage['toolCalls'] : [];
            $hasToolCalls = $toolCalls !== [];

            if ($hasToolCalls) {
                foreach ($toolCalls as $toolCall) {
                    $result = $this->executeTool(
                        $schemaBuilder,
                        $toolCall['name'],
                        $toolCall['input'],
                    );

                    if ($toolCall['name'] === ToolSchemaBuilder::TOOL_SEARCH) {
                        /** @var array<int, string> $toolNames */
                        $toolNames = array_values(array_filter(
                            is_array($result['revealedTools'] ?? null) ? $result['revealedTools'] : [],
                            'is_string',
                        ));

                        foreach ($toolNames as $toolName) {
                            $revealedTools[$toolName] = $toolName;
                        }

                        $tools = array_values($schemaBuilder->getTools(
                            toolNames: array_values($revealedTools),
                            compact: true,
                            includeToolSearch: true,
                        ));
                    }

                    $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $this->sendSseEvent('tool_end', [
                        'id'     => $toolCall['id'],
                        'name'   => $toolCall['name'],
                        'result' => $result,
                    ]);

                    $toolResultMessage = [
                        'role'       => 'tool',
                        'toolCallId' => $toolCall['id'],
                        'name'       => $toolCall['name'],
                        'content'    => $resultJson ?: '{}',
                    ];

                    $messages[]    = $toolResultMessage;
                    $newMessages[] = $toolResultMessage;
                }
            }
        } while ($hasToolCalls && $iterations < self::MAX_LOOP_ITERATIONS);

        if ($iterations >= self::MAX_LOOP_ITERATIONS) {
            $this->sendSseEvent('error', ['message' => 'Maximum tool-use iterations reached.']);
        }

        $this->sendSseEvent('done', ['messages' => $newMessages]);

        return null;
    }

    // ------------------------------------------------------------------
    // Tool execution
    // ------------------------------------------------------------------

    /**
     * Execute a tool by name with the given input.
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function executeTool(ToolSchemaBuilder $schema, string $name, array $input): array
    {
        if ($name === ToolSchemaBuilder::TOOL_SEARCH) {
            $query = is_string($input['query'] ?? null) ? $input['query'] : null;
            /** @var array<string>|null $names */
            $names = is_array($input['names'] ?? null) ? $input['names'] : null;
            $limit = is_int($input['limit'] ?? null) ? $input['limit'] : 8;

            return $schema->searchTools($query, $names, $limit);
        }

        $class = $schema->getClass($name);

        if ($class === null) {
            return ['error' => "Unknown tool: {$name}"];
        }

        try {
            $tool = Craft::$container->get($class);

            if (! is_callable($tool)) {
                return ['error' => "Tool {$name} is not callable."];
            }

            $raw = $tool(...$input);

            /** @var array<string, mixed> */
            return is_array($raw) ? $raw : ['result' => $raw];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ------------------------------------------------------------------
    // SSE helpers
    // ------------------------------------------------------------------

    /**
     * Flush output buffers and send SSE headers.
     */
    private function beginSse(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    }

    /**
     * Emit a single SSE frame and flush.
     *
     * @param array<string, mixed> $data
     */
    private function sendSseEvent(string $event, array $data = []): void
    {
        if (connection_aborted()) {
            return;
        }

        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        flush();
    }
}
