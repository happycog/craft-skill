<?php

declare(strict_types=1);

namespace happycog\craftmcp\mcp;

use Craft;
use CuyZ\Valinor\MapperBuilder;
use happycog\craftmcp\cli\CommandRouter;
use Mcp\Schema\Result\CallToolResult;

final class McpToolExecutor
{
    private readonly ToolErrorFormatter $toolErrorFormatter;

    public function __construct(?ToolErrorFormatter $toolErrorFormatter = null)
    {
        $this->toolErrorFormatter = $toolErrorFormatter ?? new ToolErrorFormatter();
    }

    /**
     * @param class-string $toolClass
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>|CallToolResult
     */
    public function execute(string $toolClass, string $toolName, array $arguments): array|CallToolResult
    {
        try {
            $mapper = (new MapperBuilder())
                ->allowPermissiveTypes()
                ->allowSuperfluousKeys()
                ->allowScalarValueCasting()
                ->argumentsMapper();

            $router = new CommandRouter($mapper);

            return $router->routeToolClass($toolClass, [], $arguments);
        } catch (\InvalidArgumentException $exception) {
            return $this->toolErrorFormatter->formatToolError($toolName, $exception);
        } catch (\Throwable $exception) {
            Craft::error($exception, 'mcp-tool');

            return $this->toolErrorFormatter->formatToolError($toolName, $exception);
        }
    }
}
