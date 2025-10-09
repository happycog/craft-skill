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
        $returnType = $method->getReturnType();
        $as = $this->as ?? ($returnType instanceof \ReflectionNamedType ? $returnType->getName() : $method->getName());

        if ($this->singleton) {
            Craft::$container->setSingleton($as, $callable);
        } else {
            Craft::$container->set($as, $callable);
        }
    }
}
