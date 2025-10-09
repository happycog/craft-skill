<?php

namespace happycog\craftmcp\base;

use craft\base\Plugin as BasePlugin;
use craft\console\Request;
use happycog\craftmcp\attributes\BindToContainer;
use happycog\craftmcp\attributes\Init;
use happycog\craftmcp\attributes\RegisterListener;

class Plugin extends BasePlugin
{
    public static Plugin $plugin;

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->controllerNamespace = 'happycog\\craftmcp\\controllers';

        if (\Craft::$app->getRequest() instanceof Request) {
            $this->controllerNamespace = 'happycog\\craftmcp\\console';
        }

        \Craft::setAlias('@happycog/craftmcp', $this->getBasePath());

        $methods = (new \ReflectionClass($this))->getMethods();
        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                $callable = $method->getClosure($this);
                /** @phpstan-ignore-next-line */
                $instance($callable, $method, $attribute);
            }
        }
    }
}
