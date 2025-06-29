<?php

namespace markhuot\craftmcp\messages\tools;

use Craft;
use PhpMcp\Server\Definitions\ToolDefinition;
use PhpMcp\Server\Server;

class CallMessage
{
    public function __invoke(int $id, string $method, array $params): array
    {
        $server = Craft::$container->get(Server::class);

        /** @var ToolDefinition $tool */
        $tool = $server->getRegistry()->findTool($params['name']);

        try {
            $result = (new ($tool->getClassName()))(...$params['arguments']);
        }
        catch (\Throwable $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'data' => $e->getTraceAsString(),
                ],
            ];
        }

        if (is_array($result)) {
            $content = [
                //'type' => 'structuredContent', // not supported yet
                //'text' => $result,
                'type' => 'text',
                'text' => json_encode($result, JSON_PRETTY_PRINT),
            ];
        } else {
            $content = [
                'type' => 'text',
                'text' => (string) $result,
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [$content],
            ],
        ];
    }
}
