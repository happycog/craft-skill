<?php

namespace happycog\craftmcp\actions;

use craft\models\UserGroup;
use craft\services\UserGroups;

class ResolveUserGroupIds
{
    public function __construct(
        protected UserGroups $userGroupsService,
    ) {
    }

    /**
     * @param list<int>|null $groupIds
     * @param list<string>|null $groupHandles
     * @return list<int>
     */
    public function __invoke(?array $groupIds = null, ?array $groupHandles = null): array
    {
        if ($groupIds === null && $groupHandles === null) {
            return [];
        }

        throw_unless($groupIds === null || $groupHandles === null, \InvalidArgumentException::class, 'Provide either groupIds or groupHandles, not both.');

        if ($groupIds !== null) {
            foreach ($groupIds as $groupId) {
                throw_unless($this->userGroupsService->getGroupById($groupId) instanceof UserGroup, \InvalidArgumentException::class, "User group with ID {$groupId} not found.");
            }

            return array_values(array_unique($groupIds));
        }

        $resolvedGroupIds = [];
        foreach ($groupHandles as $handle) {
            $group = $this->userGroupsService->getGroupByHandle($handle);
            throw_unless($group instanceof UserGroup && $group->id !== null, \InvalidArgumentException::class, "User group with handle '{$handle}' not found.");
            $resolvedGroupIds[] = $group->id;
        }

        return array_values(array_unique($resolvedGroupIds));
    }
}
