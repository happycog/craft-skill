<?php

/**
 * AI Configuration
 *
 * Copy this file to your project's `config/` directory:
 *
 *   cp vendor/happycog/craft-skill/stubs/config/ai.php config/ai.php
 *
 * Then fill in your API key below. All other keys are optional.
 *
 * @see https://github.com/happycog/craft-skill
 */

return [

    // ── Provider ────────────────────────────────────────────────────
    // 'anthropic' (default) or 'openai' (also works with any
    // OpenAI-compatible API: Together, Groq, Ollama, LM Studio, etc.)
    'provider' => 'anthropic',

    // ── API Key (required) ──────────────────────────────────────────
    // You can also use an environment variable:
    //   'apiKey' => getenv('AI_API_KEY'),
    'apiKey' => '',

    // ── Model ───────────────────────────────────────────────────────
    // Leave blank (or omit) for the provider default:
    //   Anthropic → claude-sonnet-4-20250514
    //   OpenAI    → gpt-4o
    // 'model' => '',

    // ── Base URL (OpenAI provider only) ─────────────────────────────
    // Override when using an OpenAI-compatible service.
    // Defaults to https://api.openai.com/v1
    // 'baseUrl' => 'https://api.openai.com/v1',

    // ── System Prompt ───────────────────────────────────────────────
    // Custom system prompt for the AI assistant. Leave blank (or omit)
    // to use the built-in default that describes Craft CMS capabilities.
    // 'systemPrompt' => '',

];
