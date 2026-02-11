<?php

namespace happycog\craftmcp\helpers;

use Craft;

/**
 * Get service from container with proper type hinting.
 *
 * @template T
 * @param class-string<T> $className
 * @return T
 */
function service(string $className)
{
    return Craft::$container->get($className);
}
