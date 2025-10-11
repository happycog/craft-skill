<?php

namespace happycog\craftmcp\base;

use Craft;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    public function get(string $id)
    {
        return Craft::$container->get($id);
    }

    public function has(string $id): bool
    {
        return Craft::$container->has($id);
    }
}
