<?php

namespace markhuot\craftmcp\messages\notifications;

use stdClass;

class InitializedMessage
{
    public function __invoke()
    {
        return [
            'jsonrpc' => '2.0',
            'result' => new stdClass,
        ];
    }
}
