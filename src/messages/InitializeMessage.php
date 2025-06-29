<?php

namespace markhuot\craftmcp\messages;

use Craft;
use PhpMcp\Server\Server;

class InitializeMessage
{
    public function __invoke(int $id): array
    {
        $server = Craft::$container->get(Server::class);

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => $server->getConfiguration()->capabilities->toInitializeResponseArray(),
                'serverInfo' => [
                    'name' => $server->getConfiguration()->serverName,
                    'version' => $server->getConfiguration()->serverVersion,
                ]
            ]
        ];
    }
}
