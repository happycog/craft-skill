<?php

namespace happycog\craftmcp\attributes;

use Attribute;
use Craft;

#[Attribute(\Attribute::TARGET_METHOD)]
class BindToContainer
{
    public function __construct(
        public ?string $as = null,
        public ?bool $singleton = false,
    ) {
    }

    public function __invoke(callable $callable, \ReflectionMethod $method): void
    {
        $as = $this->as ?? $method->getReturnType()?->getName() ?? $method->getName();

        if ($this->singleton) {
            Craft::$container->setSingleton($as, $callable);
        } else {
            Craft::$container->set($as, $callable);
        }
    }
}
