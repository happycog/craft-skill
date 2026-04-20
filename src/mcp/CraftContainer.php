<?php

declare(strict_types=1);

namespace happycog\craftmcp\mcp;

use Craft;
use Mcp\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 adapter around Craft's Yii DI container.
 *
 * Used by the MCP SDK's ReferenceHandler so tool classes are constructed
 * through Craft's container (respecting any bindings registered by the
 * plugin) instead of the SDK's naive auto-wiring container.
 */
final class CraftContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new ServiceNotFoundException(\sprintf('Service "%s" not found in Craft container.', $id));
        }

        return Craft::$container->get($id);
    }

    public function has(string $id): bool
    {
        return class_exists($id) || interface_exists($id);
    }
}
