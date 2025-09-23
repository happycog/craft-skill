<?php

namespace PHPSTORM_META {

    override(\Psr\Container\ContainerInterface::get(0), map([
        '' => '@',
    ]));

    override(\yii\di\Container::get(0), map([
        '' => '@',
    ]));

    override(\Craft::$container->get(0), map([
        '' => '@',
    ]));

}