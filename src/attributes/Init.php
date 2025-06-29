<?php

namespace markhuot\craftmcp\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Init
{
    public function __invoke(callable $method): void
    {
        $method();
    }
}
