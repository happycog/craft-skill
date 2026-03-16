<?php

use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\StringHelper;
use craft\models\UserGroup;

function syncCraftEditionFromProjectConfig(): void
{
    $configuredEdition = Craft::$app->getProjectConfig()->get('system.edition');

    if (is_string($configuredEdition) && $configuredEdition !== '') {
        Craft::$app->setEdition(CmsEdition::fromHandle($configuredEdition));
    }
}

function createTestUser(string $emailPrefix = 'user-test', bool $admin = false): User
{
    syncCraftEditionFromProjectConfig();

    $existingUser = User::find()->status(null)->site('*')->one();
    if ($existingUser instanceof User) {
        return $existingUser;
    }

    $email = $emailPrefix . '-' . uniqid('', true) . '@example.com';
    $user = new User();
    $user->email = $email;
    $user->username = $email;
    $user->admin = $admin;
    $user->active = true;
    $user->pending = false;
    $user->newPassword = 'Password123!';
    $user->setScenario(User::SCENARIO_REGISTRATION);

    $saved = Craft::$app->getElements()->saveElement($user, false);
    expect($saved)->toBeTrue();

    return $user;
}

function createTestUserGroup(string $name = 'Test Group'): UserGroup
{
    syncCraftEditionFromProjectConfig();

    $group = new UserGroup();
    $group->name = $name;
    $group->handle = StringHelper::toHandle($name . ' ' . uniqid());

    $saved = Craft::$app->getUserGroups()->saveGroup($group);
    throw_unless($saved, 'Failed to save test user group: ' . json_encode($group->getErrors()));

    return $group;
}

function craftSupportsUserGroups(): bool
{
    syncCraftEditionFromProjectConfig();

    return Craft::$app->edition->value >= CmsEdition::Pro->value;
}

function craftSupportsUserPermissionAssignment(): bool
{
    syncCraftEditionFromProjectConfig();

    return Craft::$app->edition->value >= CmsEdition::Pro->value;
}

function craftCanCreateAdditionalUsers(): bool
{
    syncCraftEditionFromProjectConfig();

    return Craft::$app->getUsers()->canCreateUsers();
}
