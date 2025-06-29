<?php

namespace markhuot\craftmcp\messages\tools;

use Craft;
use PhpMcp\Server\Definitions\ToolDefinition;
use PhpMcp\Server\Server;

class ListMessage
{
    public function __invoke(int $id): array
    {
        $server = Craft::$container->get(Server::class);
        $tools = $server->getRegistry()->allTools();
        $toolDefinitions = collect($tools)->map(fn (ToolDefinition $tool) => $tool->toArray());

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => array_values($toolDefinitions->toArray()),
            ],
        ];
    }
}
