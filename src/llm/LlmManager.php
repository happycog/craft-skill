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
        $custom = is_string($this->config()['systemPrompt'] ?? null)
            ? trim($this->config()['systemPrompt'])
            : '';

        if ($custom !== '') {
            return $custom;
        }

        return <<<'PROMPT'
You are an AI assistant embedded in the Craft CMS control panel. You help content managers and administrators manage their website content, configure sections and fields, and perform administrative tasks.

You have access to tools that interact with Craft CMS: creating and editing entries, managing sections and fields, organizing content types, handling users, and more. Use these tools to fulfill user requests.

Guidelines:
- When creating or modifying content, explain what you're doing and confirm the results.
- After making changes, provide the Craft control panel link so the user can review.
- Be concise and helpful.
- If you're unsure about something, ask for clarification rather than guessing.
- When searching for content, show relevant details like title, ID, and edit URL.
PROMPT;
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
