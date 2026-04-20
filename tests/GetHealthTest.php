<?php

use happycog\craftmcp\tools\GetHealth;

test('GetHealth tool returns expected structure', function () {
    $tool = Craft::$container->get(GetHealth::class);
    $result = $tool->__invoke();

    expect($result)->toHaveKey('status')
        ->and($result['status'])->toBe('ok')
        ->and($result)->toHaveKey('plugin')
        ->and($result)->toHaveKey('craft')
        ->and($result)->toHaveKey('site')
        ->and($result['plugin'])->toHaveKey('name')
        ->and($result['plugin'])->toHaveKey('version')
        ->and($result['plugin'])->toHaveKey('installed')
        ->and($result['craft'])->toHaveKey('version')
        ->and($result['craft'])->toHaveKey('edition')
        ->and($result['site'])->toHaveKey('name')
        ->and($result['site'])->toHaveKey('baseUrl');
});
