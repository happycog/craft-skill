<?php

use happycog\craftmcp\tools\GetUsers;

beforeEach(function () {
    $this->tool = Craft::$container->get(GetUsers::class);
});

it('lists users', function () {
    createTestUser();

    $response = $this->tool->__invoke(limit: 10);

    expect($response['results'])->not->toBeEmpty();
});

it('filters users by group handle', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    $user = createTestUser();
    $group = createTestUserGroup('Members');
    Craft::$app->getUsers()->assignUserToGroups($user->id, [$group->id]);

    $response = $this->tool->__invoke(groupHandle: $group->handle);

    expect(collect($response['results'])->pluck('id'))->toContain($user->id);
});
