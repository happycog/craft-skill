<?php

use happycog\craftmcp\tools\UpdateUser;

beforeEach(function () {
    $this->tool = Craft::$container->get(UpdateUser::class);
});

it('updates a user by username', function () {
    $user = createTestUser();

    $response = $this->tool->__invoke(
        username: $user->username,
        fullName: 'Updated User',
    );

    expect($response['fullName'])->toBe('Updated User');
});

it('updates a user permissions when supported', function () {
    if (!craftSupportsUserPermissionAssignment()) {
        $this->markTestSkipped('Direct user permissions require Craft Pro in this environment.');
    }

    $user = createTestUser();

    $response = $this->tool->__invoke(
        username: $user->username,
        permissions: ['accesscp', 'custompermission:updated'],
    );

    expect($response['permissions'])->toContain('custompermission:updated');
});
