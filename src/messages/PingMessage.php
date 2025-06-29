<?php

namespace markhuot\craftmcp\messages;

use stdClass;

class PingMessage
{
    public function __invoke(int $id): array
    {

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => new stdClass,
        ];
    }
}
