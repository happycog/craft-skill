<?php

it('loads the plugin', function () {
    $version = Craft::$app->version;
    expect($version)->toBe('5.9.9');

    $plugin = Craft::$app->getPlugins()->getAllPlugins();
    expect($plugin)->not->toBeNull();
});
