<?php

use happycog\craftmcp\tools\CreateUserGroup;

test('creates a user group with custom permissions', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    $tool = Craft::$container->get(CreateUserGroup::class);

    $response = $tool->__invoke(
        name: 'Publishers',
        permissions: ['accesscp', 'custompermission:publish'],
    );

    expect($response['name'])->toBe('Publishers')
        ->and($response['permissions'])->toContain('custompermission:publish');
});
