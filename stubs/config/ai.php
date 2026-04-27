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
    // 'anthropic' (default), 'openai' (also works with any OpenAI-compatible
    // API: Together, Groq, Ollama, LM Studio, etc.), or 'opencode' (connects
    // to a locally-running OpenCode server — https://opencode.ai/docs/server/).
    'provider' => 'anthropic',

    // ── API Key ─────────────────────────────────────────────────────
    // Required for 'anthropic' and 'openai'. For 'opencode' leave blank
    // unless your OpenCode server has OPENCODE_SERVER_PASSWORD set, in
    // which case put that password here.
    // You can also use an environment variable:
    //   'apiKey' => getenv('AI_API_KEY'),
    'apiKey' => '',

    // ── Model ───────────────────────────────────────────────────────
    // Leave blank (or omit) for the provider default:
    //   Anthropic → claude-sonnet-4-20250514
    //   OpenAI    → gpt-4o
    //   OpenCode  → whatever the OpenCode server is configured to use
    // 'model' => '',

    // ── Base URL (openai & opencode providers) ──────────────────────
    // Override when using an OpenAI-compatible service, or to point at a
    // non-default OpenCode server address.
    //   OpenAI defaults to  https://api.openai.com/v1
    //   OpenCode defaults to http://127.0.0.1:4096
    // 'baseUrl' => 'https://api.openai.com/v1',

    // ── Directory (opencode provider only) ──────────────────────────
    // Sent as `?directory=` on every OpenCode request so the server
    // scopes the session to this Craft project (sets projectID and cwd)
    // and picks up any opencode.json at the project root.
    // Defaults to a subdirectory of the project root (tries @config,
    // then @templates, then falls back to @root) — a subdirectory is
    // required because OpenCode's config walker skips project-local
    // files when you hand it the git root directly.
    // Override if your OpenCode server should operate elsewhere.
    // 'directory' => '/path/to/project/sub-dir',

    // ── MCP HTTP Path ───────────────────────────────────────────────
    // URL path where the HTTP MCP endpoint is available.
    // Example: 'mcp' => https://yoursite.test/mcp
    'mcpPath' => 'mcp',

    // ── MCP Session TTL (seconds) ───────────────────────────────────
    // Sessions older than this are treated as expired and rejected with
    // "Session not found or has expired." Clients like OpenCode hold a
    // single session open for the life of the `opencode serve` process and
    // only hit the server when the user sends a chat message, so short
    // TTLs cause mysterious failures after idle periods. Defaults to
    // 30 days when omitted.
    // 'mcpSessionTtl' => 2592000,

    // ── System Prompt ───────────────────────────────────────────────
    // Custom system prompt for the AI assistant. Leave blank (or omit)
    // to use the built-in default that describes Craft CMS capabilities.
    // 'systemPrompt' => '',

];
