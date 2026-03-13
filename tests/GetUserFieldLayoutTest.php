<?php

use craft\elements\User;
use happycog\craftmcp\tools\GetUserFieldLayout;

test('returns the global user field layout', function () {
    $tool = Craft::$container->get(GetUserFieldLayout::class);

    $response = $tool->__invoke();

    expect($response['fieldLayout']['id'])->toBe(GetUserFieldLayout::PLACEHOLDER_ID)
        ->and($response['fieldLayout']['type'])->toBe(User::class)
        ->and($response['settingsUrl'])->toContain('settings/users');
});
