<?php

use happycog\craftmcp\tools\GetUserGroup;

test('gets a user group by handle', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    $group = createTestUserGroup('Managers');
    $tool = Craft::$container->get(GetUserGroup::class);

    $response = $tool->__invoke(handle: $group->handle);

    expect($response['handle'])->toBe($group->handle);
});
