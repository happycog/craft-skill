<?php

declare(strict_types=1);

namespace happycog\craftmcp\controllers;

use Craft;
use CuyZ\Valinor\MapperBuilder;
use craft\web\Controller as CraftController;
use happycog\craftmcp\cli\CommandRouter;
use happycog\craftmcp\cli\ValidationErrorFormatter;
use happycog\craftmcp\llm\LlmManager;
use happycog\craftmcp\llm\ToolSchemaBuilder;
use yii\web\Response;
use yii\web\ForbiddenHttpException;

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
     *   pageContext — structured request/element context for the prompt
     */
    public function actionStream(): Response
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser === null || !$currentUser->can('accessCp')) {
            throw new ForbiddenHttpException('You must be logged in with control panel access to use the chat.');
        }

        // PHP's default session handler holds an exclusive lock on the session
        // file for the whole request. The agentic loop can run for minutes, so
        // keeping the lock would block every other request from the same
        // browser (page loads, XHRs, even other chat streams) until we finish.
        // We've already read the user's identity — close the session now so
        // the lock is released before the long-running work begins.
        Craft::$app->getSession()->close();

        // Give the agentic loop generous room to finish.
        set_time_limit(300);

        $rawBody = file_get_contents('php://input') ?: '{}';
        /** @var array<string, mixed> $input */
        $input   = json_decode($rawBody, true) ?? [];

        $newText = is_string($input['message'] ?? null) ? trim($input['message']) : '';
        /** @var array<string, mixed>|null $pageContext */
        $pageContext = is_array($input['pageContext'] ?? null) ? $input['pageContext'] : null;

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
        $response = Craft::$app->getResponse();
        // We're writing headers + body ourselves via native header()/echo.
        // Mark the Response as already sent so Application::run() skips its
        // own $response->send() — that would blow up sendHeaders() and append
        // an HTML error page to the SSE body the client just finished reading.
        $response->isSent = true;
        // Push bytes out immediately so intermediaries (nginx, CF, browser) commit
        // to the stream before the first slow upstream call.
        $this->sendSseComment('stream-open');

        // ── Build conversation ───────────────────────────────────────
        $messages   = $history;
        $messages[] = ['role' => 'user', 'content' => $newText];

        $schemaBuilder = new ToolSchemaBuilder();
        $tools         = array_values($schemaBuilder->getTools(includeToolSearch: true, minimal: true, includeChatOnly: true));
        $systemPrompt  = $llm->buildSystemPrompt($pageContext);

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

                        // Heartbeats from drivers are surfaced as SSE comments, not
                        // data events — they only exist to keep the socket alive
                        // while the upstream LLM is silent.
                        if ($eventType === 'heartbeat') {
                            $this->sendSseComment('driver-wait');
                            return;
                        }

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
                    // Keep the socket warm while the (potentially slow) tool runs.
                    $this->sendSseComment('tool-' . $toolCall['name']);

                    $result = $this->executeTool(
                        $schemaBuilder,
                        $toolCall['name'],
                        $toolCall['input'],
                    );

                    $resultContent = is_string($result)
                        ? $result
                        : (json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

                    $this->sendSseEvent('tool_end', [
                        'id' => $toolCall['id'],
                        'name' => $toolCall['name'],
                        'result' => $result,
                        'isError' => is_string($result) || (is_array($result) && array_key_exists('error', $result)),
                    ]);

                    $toolResultMessage = [
                        'role'       => 'tool',
                        'toolCallId' => $toolCall['id'],
                        'name'       => $toolCall['name'],
                        'content'    => $resultContent,
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

        // Must return the Response (not null). Craft's _processActionRequest
        // treats a null return as "no action matched" and falls through to
        // regular URL routing, which 404s on /actions/* URLs — that 404 is
        // what triggers the error page appended to the SSE body.
        return $response;
    }

    // ------------------------------------------------------------------
    // Tool execution
    // ------------------------------------------------------------------

    /**
     * Execute a tool by name with the given input.
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>|string
     */
    private function executeTool(ToolSchemaBuilder $schema, string $name, array $input): array|string
    {
        if ($name === ToolSchemaBuilder::TOOL_SEARCH) {
            $query = is_string($input['query'] ?? null) ? $input['query'] : null;
            /** @var array<string>|null $names */
            $names = is_array($input['names'] ?? null) ? $input['names'] : null;
            $limit = is_int($input['limit'] ?? null) ? $input['limit'] : 8;

            return $schema->searchTools($query, $names, $limit, includeChatOnly: true);
        }

        $class = $schema->getClass($name);

        if ($class === null) {
            return ['error' => "Unknown tool: {$name}"];
        }

        try {
            $mapper = (new MapperBuilder())
                ->allowPermissiveTypes()
                ->allowSuperfluousKeys()
                ->allowScalarValueCasting()
                ->argumentsMapper();

            $router = new CommandRouter($mapper);
            return $router->routeToolClass($class, [], $input);
        } catch (\CuyZ\Valinor\Mapper\MappingError $e) {
            return (new ValidationErrorFormatter())->formatMappingError($e, $name);
        } catch (\InvalidArgumentException $e) {
            return (new ValidationErrorFormatter())->formatToolArgumentError($name, $e->getMessage());
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

    /**
     * Emit an SSE comment frame to keep the connection alive during
     * otherwise-silent server-side work (LLM round-trip, slow tool call).
     *
     * Browsers and SSE parsers ignore `:`-prefixed frames entirely, but the
     * bytes flowing through reset idle timers on reverse proxies and
     * client-side fetch readers.
     */
    private function sendSseComment(string $note = ''): void
    {
        if (connection_aborted()) {
            return;
        }

        // Strip anything that could break the SSE framing (newlines, CR).
        $safe = preg_replace('/[\r\n]+/', ' ', $note) ?? '';
        echo ': ' . $safe . "\n\n";
        flush();
    }
}
