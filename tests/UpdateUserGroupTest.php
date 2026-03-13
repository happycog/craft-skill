<?php

use happycog\craftmcp\tools\UpdateUserGroup;

test('updates a user group permissions', function () {
    if (!craftSupportsUserGroups()) {
        $this->markTestSkipped('User groups require Craft Pro in this environment.');
    }

    $group = createTestUserGroup('Reviewers');
    $tool = Craft::$container->get(UpdateUserGroup::class);

    $response = $tool->__invoke(
        groupId: $group->id,
        permissions: ['accesscp', 'custompermission:review'],
    );

    expect($response['permissions'])->toContain('custompermission:review');
});
