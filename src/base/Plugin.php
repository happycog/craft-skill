<?php

namespace markhuot\craftmcp\base;

use craft\base\Plugin as BasePlugin;
use craft\console\Request;
use markhuot\craftmcp\attributes\BindToContainer;
use markhuot\craftmcp\attributes\Init;
use markhuot\craftmcp\attributes\RegisterListener;

class Plugin extends BasePlugin
{
    public static Plugin $plugin;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->controllerNamespace = 'markhuot\\craftmcp\\controllers';

        if (\Craft::$app->getRequest() instanceof Request) {
            $this->controllerNamespace = 'markhuot\\craftmcp\\console';
        }

        $methods = (new \ReflectionClass($this))->getMethods();
        foreach ($methods as $method) {
            foreach ($method->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                $instance($method->getClosure($this), $method, $attribute);
            }
        }
    }
}
