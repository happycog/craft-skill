<?php

use happycog\craftmcp\tools\GetUserGroups;

test('lists user groups', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    createTestUserGroup('Authors');
    $tool = Craft::$container->get(GetUserGroups::class);

    $response = $tool->__invoke();

    expect(collect($response['results'])->pluck('name'))->toContain('Authors');
});
