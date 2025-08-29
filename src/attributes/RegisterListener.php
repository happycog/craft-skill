<?php

namespace happycog\craftmcp\attributes;

use Attribute;
use yii\base\Event;

#[Attribute(\Attribute::TARGET_METHOD)]
class RegisterListener
{
    public function __construct(
        public string $class,
        public string $event,
    ) {
    }

    public function __invoke(callable $callable, \ReflectionMethod $method): void
    {
        $class = $this->class;
        $event = $this->event;
        Event::on($class, $event, $callable);
    }
}
