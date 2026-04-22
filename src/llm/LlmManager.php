<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\UrlHelper;

/**
 * Factory that resolves the active LLM driver from `config/ai.php`.
 *
 * The config file should return an array with keys:
 *   - provider   — 'anthropic' (default) or 'openai'
 *   - apiKey     — your LLM provider API key
 *   - model      — model identifier (optional, defaults per-provider)
 *   - baseUrl    — OpenAI-compatible base URL (optional, only for openai provider)
 *   - systemPrompt — custom system prompt (optional)
 *
 * Copy `vendor/happycog/craft-skill/stubs/config/ai.php` into your
 * project's `config/` directory to get started.
 *
 * Usage:
 *   $manager = Craft::$container->get(LlmManager::class);
 *   $driver  = $manager->driver();  // AnthropicDriver | OpenAiDriver
 */
final class LlmManager
{
    public const AI_WIDGET_SYSTEM_PROMPT = 'ai_widget_system_prompt';

    private ?LlmDriverInterface $resolved = null;

    /** @var array<string, mixed>|null */
    private ?array $configCache = null;

    /**
     * Return the configured driver, creating it on first access.
     */
    public function driver(): LlmDriverInterface
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $config = $this->config();

        $provider = is_string($config['provider'] ?? null) ? $config['provider'] : 'anthropic';
        $apiKey   = is_string($config['apiKey'] ?? null) ? $config['apiKey'] : '';
        $model    = is_string($config['model'] ?? null) ? $config['model'] : '';
        $baseUrl  = is_string($config['baseUrl'] ?? null) ? $config['baseUrl'] : '';

        $this->resolved = match ($provider) {
            'openai' => new OpenAiDriver(
                apiKey:  $apiKey,
                model:   $model ?: 'gpt-4o',
                baseUrl: $baseUrl ?: 'https://api.openai.com/v1',
            ),
            default => new AnthropicDriver(
                apiKey: $apiKey,
                model:  $model ?: 'claude-sonnet-4-20250514',
            ),
        };

        return $this->resolved;
    }

    /**
     * Return the configured (or default) system prompt.
     */
    public function systemPrompt(): string
    {
        return $this->buildSystemPrompt();
    }

    /**
     * Build the effective system prompt, optionally scoped to page context.
     *
     * @param array<string, mixed>|null $pageContext
     */
    public function buildSystemPrompt(?array $pageContext = null): string
    {
        $custom = is_string($this->config()['systemPrompt'] ?? null)
            ? trim($this->config()['systemPrompt'])
            : '';

        $prompt = $custom !== '' ? $custom : <<<'PROMPT'
You are an AI assistant embedded in the Craft CMS chat widget. You help content managers and administrators manage their website content, configure sections and fields, and perform administrative tasks.

You have access to tools that interact with Craft CMS: creating and editing entries, managing sections and fields, organizing content types, handling users, and more. Use these tools to fulfill user requests.

ToolSearch is available to help you discover relevant tools and inspect parameter summaries, but it is optional. If you already know the right tool for the task, you may call it directly.

The initial tool definitions may be intentionally minimal to keep context small. When a tool's parameters are unclear, use `ToolSearch` to inspect likely tools before calling them.

Guidelines:
- When a user asks to change the content of an existing entry **always** create or update a draft.
- If you know a draftId use draft tools.
- Use live entry updates for content **only** if the user clearly asks to publish immediately or avoid drafts.
- Always call `OpenUrl` after a content change so the user can see their changes in the browser. You can call this with any URL (an entry URL, a preview URL, etc...)
- After making changes, provide the Craft control panel link so the user can review.
- When a tool call returns an error, read the full tool response carefully before retrying because it often includes helpful formatting, debugging tips, or corrected parameter examples.

**Never** edit live entry content directly unless you are specifically instructed to do so by the user.
**Never** apply draft changes unless you are specifically instructed to do so by the user.
PROMPT;

        $pageContext = $this->normalizePageContext($pageContext);

        if ($pageContext !== []) {
            $prompt .= "\n\nCurrent page context:";

            foreach ($pageContext as $label => $value) {
                $prompt .= "\n- {$label}: {$value}";
            }
        }

        return $prompt;
    }

    /**
     * MCP prompt that exposes the AI widget's system prompt.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function aiWidgetSystemPrompt(
        ?string $currentUrl = null,
        ?string $requestPath = null,
        ?string $requestedRoute = null,
        ?array $routeParams = null,
        ?int $elementId = null,
        ?string $elementType = null,
        ?string $elementTitle = null,
        ?string $elementSlug = null,
        ?string $elementUri = null,
        ?int $draftId = null,
        ?int $siteId = null,
    ): array
    {
        return [[
            'role' => 'assistant',
            'content' => $this->buildSystemPrompt([
                'currentUrl' => $currentUrl,
                'requestPath' => $requestPath,
                'requestedRoute' => $requestedRoute,
                'routeParams' => $routeParams,
                'elementId' => $elementId,
                'elementType' => $elementType,
                'elementTitle' => $elementTitle,
                'elementSlug' => $elementSlug,
                'elementUri' => $elementUri,
                'draftId' => $draftId,
                'siteId' => $siteId,
            ]),
        ]];
    }

    /**
     * @param array<string, mixed>|null $pageContext
     * @return array<string, string>
     */
    public function normalizePageContext(?array $pageContext): array
    {
        if ($pageContext === null) {
            return [];
        }

        $normalized = [];

        $fieldMap = [
            'surface' => 'Current surface',
            'currentUrl' => 'URL',
            'controlPanelUrl' => 'Control panel URL',
            'requestPath' => 'Request path',
            'requestedRoute' => 'Requested route',
            'elementId' => 'Element ID',
            'elementType' => 'Element type',
            'elementTitle' => 'Element title',
            'elementSlug' => 'Element slug',
            'elementUri' => 'Element URI',
            'draftId' => 'Draft ID',
            'siteId' => 'Site ID',
        ];

        foreach ($fieldMap as $key => $label) {
            $value = $pageContext[$key] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_scalar($value)) {
                $normalized[$label] = (string) $value;
            }
        }

        $routeParams = $pageContext['routeParams'] ?? null;

        if (is_array($routeParams) && $routeParams !== []) {
            $routeParamsJson = json_encode($this->normalizeRouteParams($routeParams), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (is_string($routeParamsJson) && $routeParamsJson !== '') {
                $normalized['Route params'] = $routeParamsJson;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function pageContext(?ElementInterface $element = null): array
    {
        $request = Craft::$app->getRequest();
        $urlManager = Craft::$app->getUrlManager();

        $context = [
            'surface' => $request->getIsCpRequest() ? 'cp' : 'site',
            'currentUrl' => $request->getAbsoluteUrl(),
            'requestPath' => $request->getPathInfo(),
            'requestedRoute' => Craft::$app->requestedRoute,
            'routeParams' => $this->normalizeRouteParams($urlManager->getRouteParams() ?? []),
        ];

        $controlPanelUrl = UrlHelper::cpUrl('');

        if (is_string($controlPanelUrl) && trim($controlPanelUrl) !== '') {
            $context['controlPanelUrl'] = $controlPanelUrl;
        }

        if ($element !== null) {
            $context['elementId'] = $element->id;
            $context['elementType'] = $element::class;
            $context['siteId'] = $element->siteId;

            if ($element->getIsDraft()) {
                $context['draftId'] = $element->id;
            }

            if (property_exists($element, 'title') && is_string($element->title) && trim($element->title) !== '') {
                $context['elementTitle'] = $element->title;
            }

            if (property_exists($element, 'slug') && is_string($element->slug) && trim($element->slug) !== '') {
                $context['elementSlug'] = $element->slug;
            }

            if (property_exists($element, 'uri') && is_string($element->uri) && trim($element->uri) !== '') {
                $context['elementUri'] = $element->uri;
            }
        }

        $draftId = $context['routeParams']['draftId'] ?? null;

        if (is_int($draftId) || is_string($draftId) && ctype_digit($draftId)) {
            $context['draftId'] = (int) $draftId;
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $routeParams
     * @return array<string, mixed>
     */
    private function normalizeRouteParams(array $routeParams): array
    {
        $normalized = [];

        foreach ($routeParams as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeRouteParams($value);
                continue;
            }

            if ($value instanceof ElementInterface) {
                $normalized[$key] = [
                    'id' => $value->id,
                    'type' => $value::class,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Check whether the driver is properly configured (API key present).
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->config()['apiKey'] ?? '';

        return is_string($apiKey) && trim($apiKey) !== '';
    }

    /**
     * Load and cache the config/ai.php file.
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        if ($this->configCache !== null) {
            return $this->configCache;
        }

        $raw = Craft::$app->getConfig()->getConfigFromFile('ai');

        $this->configCache = is_array($raw) ? $raw : [];

        return $this->configCache;
    }
}
