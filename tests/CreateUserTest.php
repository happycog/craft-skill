<?php

use happycog\craftmcp\tools\CreateUser;

beforeEach(function () {
    $this->tool = Craft::$container->get(CreateUser::class);
});

it('creates a user', function () {
    if (!craftCanCreateAdditionalUsers()) {
        $this->markTestSkipped('The current Craft edition has already reached its user limit.');
    }

    $response = $this->tool->__invoke(
        email: 'created-' . uniqid() . '@example.com',
        newPassword: 'Password123!',
        fullName: 'Created User',
    );

    expect($response['email'])->toBeString()
        ->and($response['fullName'])->toBe('Created User');
});

it('creates a user with group handles and permissions when supported', function () {
    if (!craftCanCreateAdditionalUsers()) {
        $this->markTestSkipped('The current Craft edition has already reached its user limit.');
    }

    if (!craftSupportsUserGroups() || !craftSupportsUserPermissionAssignment()) {
        $this->markTestSkipped('User groups and direct permissions require Craft Pro in this environment.');
    }

    $group = createTestUserGroup('Editors');

    $response = $this->tool->__invoke(
        email: 'created-' . uniqid() . '@example.com',
        newPassword: 'Password123!',
        fullName: 'Created User',
        groupHandles: [$group->handle],
        permissions: ['accesscp', 'custompermission:test'],
    );

    expect($response['email'])->toBeString()
        ->and($response['groups'][0]['handle'])->toBe($group->handle)
        ->and($response['permissions'])->toContain('custompermission:test');
});
