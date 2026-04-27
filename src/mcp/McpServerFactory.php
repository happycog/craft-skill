<?php

declare(strict_types=1);

namespace happycog\craftmcp\mcp;

use Craft;
use happycog\craftmcp\base\CommandMap;
use happycog\craftmcp\llm\LlmManager;
use happycog\craftmcp\llm\ToolSchemaBuilder;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Psr\Container\ContainerInterface;

/**
 * Builds an MCP Server with every Craft skill tool registered.
 *
 * This is the bridge between our invokable tool classes (src/tools/*) and
 * the MCP SDK: each tool is registered via `addTool()` so the SDK can
 * generate its input schema from the tool's `__invoke` signature + PHPDoc
 * and route `tools/call` requests through Craft's DI container.
 *
 * Tool names default to each class's PascalCase short name (e.g.
 * `CreateSection`) — the SDK's built-in behaviour when `__invoke` is the
 * handler and no explicit name is supplied.
 */
final class McpServerFactory
{
    private const SESSION_DIRECTORY = 'craft-skills-mcp';

    /**
     * MCP session TTL, in seconds.
     *
     * The SDK's FileSessionStore defaults to 3600s (1 hour), which expires
     * long-lived client connections mid-day — OpenCode in particular keeps
     * its MCP client open for the life of the `opencode serve` process and
     * only actually dispatches tool calls when the user sends a chat message,
     * so idle windows well over an hour are the common case. When the session
     * expires the next tool call fails with "Session not found or has
     * expired." and the client does not re-initialize on its own.
     *
     * 30 days is effectively forever for any realistic use of a local dev
     * server while still letting the periodic gc() sweep reap truly orphaned
     * session files eventually.
     */
    private const SESSION_TTL_SECONDS = 30 * 24 * 60 * 60;

    private readonly ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container ?? new CraftContainer();
    }

    public function create(): Server
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('skills');
        $version = $plugin?->getVersion() ?? 'dev';
        $toolSchemaBuilder = new ToolSchemaBuilder();
        $toolExecutor = Craft::$container->get(McpToolExecutor::class);

        $builder = Server::builder()
            ->setServerInfo('Craft Skills MCP', $version, 'Craft CMS management tools exposed over the Model Context Protocol.')
            ->setContainer($this->container)
            ->setSession(new FileSessionStore($this->sessionDirectory(), ttl: $this->sessionTtl()));

        $builder->addPrompt(
            handler: [LlmManager::class, 'aiWidgetSystemPrompt'],
            name: LlmManager::AI_WIDGET_SYSTEM_PROMPT,
            description: 'Returns the system prompt used by the embedded Craft control panel AI widget.',
        );

        foreach (CommandMap::all() as $class) {
            $reflection = new \ReflectionClass($class);
            $toolName = $reflection->getShortName();
            $tool = $toolSchemaBuilder->getTool($toolName, includeChatOnly: true);

            if ($tool === null) {
                continue;
            }

            $description = is_string($tool['description'] ?? null) ? $tool['description'] : null;
            $inputSchema = is_array($tool['parameters'] ?? null) ? $tool['parameters'] : null;

            $builder->addTool(
                handler: static fn (\Mcp\Server\RequestContext $context): array|\Mcp\Schema\Result\CallToolResult => $toolExecutor->execute(
                    toolClass: $class,
                    toolName: $toolName,
                    arguments: $context->getRequest() instanceof \Mcp\Schema\Request\CallToolRequest
                        ? $context->getRequest()->arguments
                        : [],
                ),
                name: $toolName,
                description: $description,
                inputSchema: $inputSchema,
            );
        }

        return $builder->build();
    }

    private function sessionDirectory(): string
    {
        return Craft::$app->getPath()->getRuntimePath() . DIRECTORY_SEPARATOR . self::SESSION_DIRECTORY;
    }

    /**
     * Resolve the MCP session TTL, preferring an explicit override in
     * config/ai.php under the `mcpSessionTtl` key. Falls back to the 30-day
     * default.
     */
    private function sessionTtl(): int
    {
        $rawConfig = Craft::$app->getConfig()->getConfigFromFile('ai');
        $override = is_array($rawConfig) ? ($rawConfig['mcpSessionTtl'] ?? null) : null;

        if (is_int($override) && $override > 0) {
            return $override;
        }

        return self::SESSION_TTL_SECONDS;
    }
}
