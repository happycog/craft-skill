<?php

declare(strict_types=1);

namespace happycog\craftmcp\llm;

use Craft;

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
     * Build the effective system prompt, optionally scoped to a current URL.
     */
    public function buildSystemPrompt(?string $currentUrl = null): string
    {
        $custom = is_string($this->config()['systemPrompt'] ?? null)
            ? trim($this->config()['systemPrompt'])
            : '';

        $prompt = $custom !== '' ? $custom : <<<'PROMPT'
You are an AI assistant embedded in the Craft CMS control panel. You help content managers and administrators manage their website content, configure sections and fields, and perform administrative tasks.

You have access to tools that interact with Craft CMS: creating and editing entries, managing sections and fields, organizing content types, handling users, and more. Use these tools to fulfill user requests.

Tool access is discovery-based. Start by calling `ToolSearch` to find the smallest relevant set of tools for the task, inspect their parameters, and only then call the revealed tools. Do not guess tool names or parameters before using `ToolSearch`.

Guidelines:
- When a user asks to change the content of an existing entry, prefer creating or updating a draft rather than editing the live entry directly.
- Use live entry updates for content only if the user clearly asks to publish immediately or avoid drafts.
- When you create or update a draft, tell the user it is a draft and include both the Craft control panel edit link and the draft preview URL so they can review the changes safely.
- When creating or modifying content, explain what you're doing and confirm the results.
- After making changes, provide the Craft control panel link so the user can review.
- Be concise and helpful.
- If you're unsure about something, ask for clarification rather than guessing.
- When searching for content, show relevant details like title, ID, and edit URL.
PROMPT;

        $currentUrl = is_string($currentUrl) ? trim($currentUrl) : '';

        if ($currentUrl !== '') {
            $prompt .= "\n\nCurrent page URL: {$currentUrl}";
        }

        return $prompt;
    }

    /**
     * MCP prompt that exposes the AI widget's system prompt.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function aiWidgetSystemPrompt(?string $currentUrl = null): array
    {
        return [[
            'role' => 'assistant',
            'content' => $this->buildSystemPrompt($currentUrl),
        ]];
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
