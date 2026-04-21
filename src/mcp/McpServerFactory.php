<?php

declare(strict_types=1);

namespace happycog\craftmcp\mcp;

use Craft;
use happycog\craftmcp\base\CommandMap;
use Mcp\Server;
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
    private readonly ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container ?? new CraftContainer();
    }

    public function create(): Server
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('skills');
        $version = $plugin?->getVersion() ?? 'dev';

        $builder = Server::builder()
            ->setServerInfo('Craft Skills MCP', $version, 'Craft CMS management tools exposed over the Model Context Protocol.')
            ->setContainer($this->container);

        foreach (CommandMap::all() as $class) {
            $builder->addTool([$class, '__invoke']);
        }

        return $builder->build();
    }
}
