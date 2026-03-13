<?php

namespace happycog\craftmcp\actions;

use craft\db\Table;
use craft\helpers\Db;
use craft\records\UserPermission as UserPermissionRecord;
use craft\services\UserPermissions;

class SaveUserPermissions
{
    public function __construct(
        protected UserPermissions $userPermissions,
    ) {
    }

    /**
     * @param string[] $permissions
     */
    public function __invoke(int $userId, array $permissions): bool
    {
        $normalizedPermissions = array_values(array_unique(array_map('strtolower', $permissions)));
        sort($normalizedPermissions);

        Db::delete(Table::USERPERMISSIONS_USERS, [
            'userId' => $userId,
        ]);

        if ($normalizedPermissions !== []) {
            $userPermissionValues = [];

            foreach ($normalizedPermissions as $permissionName) {
                $permissionRecord = UserPermissionRecord::findOne(['name' => $permissionName]) ?? new UserPermissionRecord();
                $permissionRecord->name = $permissionName;
                $permissionRecord->save(false);

                $permissionId = $permissionRecord->id;
                throw_unless($permissionId !== null, "Failed to resolve permission ID for '{$permissionName}'.");

                $userPermissionValues[] = [$permissionId, $userId];
            }

            Db::batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $userPermissionValues);
        }

        $this->userPermissions->reset();

        return true;
    }
}
