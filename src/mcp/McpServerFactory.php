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
            ->setSession(new FileSessionStore($this->sessionDirectory()));

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
}
