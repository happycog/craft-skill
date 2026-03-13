<?php

namespace happycog\craftmcp\actions;

use craft\db\Table;
use craft\db\Query;
use craft\models\UserGroup;
use craft\services\UserPermissions;

class FormatUserGroup
{
    public function __construct(
        protected UserPermissions $userPermissions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(UserGroup $group): array
    {
        $groupId = $group->id;

        return [
            'id' => $groupId,
            'uid' => $group->uid,
            'name' => $group->name,
            'handle' => $group->handle,
            'description' => $group->description,
            'permissions' => $groupId !== null ? $this->userPermissions->getPermissionsByGroupId($groupId) : [],
            'userCount' => $groupId !== null ? (new Query())
                ->from(Table::USERGROUPS_USERS)
                ->where(['groupId' => $groupId])
                ->count() : 0,
            'cpEditUrl' => $group->getCpEditUrl(),
        ];
    }
}
