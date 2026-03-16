<?php

use happycog\craftmcp\tools\DeleteUserGroup;

test('deletes a user group by handle', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    $group = createTestUserGroup('Temporary Group');
    $tool = Craft::$container->get(DeleteUserGroup::class);

    $response = $tool->__invoke(handle: $group->handle);

    expect($response['handle'])->toBe($group->handle)
        ->and(Craft::$app->getUserGroups()->getGroupById($group->id))->toBeNull();
});
