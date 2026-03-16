<?php

use happycog\craftmcp\tools\GetAvailablePermissions;

test('lists available permissions including custom stored permissions', function () {
    syncCraftEditionFromProjectConfig();

    $user = createTestUser();
    $updateUser = Craft::$container->get(\happycog\craftmcp\tools\UpdateUser::class);
    $tool = Craft::$container->get(GetAvailablePermissions::class);

    $updateUser->__invoke(
        userId: $user->id,
        permissions: ['accesscp', 'custompermission:discoverable'],
    );

    $response = $tool->__invoke();

    expect($response)->toHaveKeys(['groups', 'allPermissions', 'allPermissionNames', 'customPermissions', 'customPermissionNames'])
        ->and($response['allPermissionNames'])->toContain('accesscp')
        ->and($response['customPermissionNames'])->toContain('custompermission:discoverable')
        ->and(collect($response['allPermissions'])->firstWhere('name', 'accesscp'))->toMatchArray([
            'name' => 'accesscp',
            'label' => 'Access the control panel',
            'isCustom' => false,
        ])
        ->and(collect($response['customPermissions'])->firstWhere('name', 'custompermission:discoverable'))->toMatchArray([
            'name' => 'custompermission:discoverable',
            'label' => 'custompermission:discoverable',
            'isCustom' => true,
        ]);
});
