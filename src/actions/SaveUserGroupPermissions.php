<?php

namespace happycog\craftmcp\actions;

use Craft;
use craft\models\UserGroup;
use craft\services\ProjectConfig;
use craft\services\UserPermissions;

class SaveUserGroupPermissions
{
    public function __construct(
        protected UserPermissions $userPermissions,
    ) {
    }

    /**
     * @param string[] $permissions
     */
    public function __invoke(UserGroup $group, array $permissions): bool
    {
        $groupUid = $group->uid;
        throw_unless(is_string($groupUid) && $groupUid !== '', 'User group UID is missing.');

        $normalizedPermissions = array_values(array_unique(array_map('strtolower', $permissions)));
        sort($normalizedPermissions);

        Craft::$app->getProjectConfig()->set(
            ProjectConfig::PATH_USER_GROUPS . '.' . $groupUid . '.permissions',
            $normalizedPermissions,
            "Update permissions for user group '{$group->handle}'",
        );

        $this->userPermissions->reset();

        return true;
    }
}
