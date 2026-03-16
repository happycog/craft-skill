<?php

namespace happycog\craftmcp\actions;

use craft\models\UserGroup;
use craft\services\UserGroups;

class ResolveUserGroup
{
    public function __construct(
        protected UserGroups $userGroupsService,
    ) {
    }

    public function __invoke(?int $groupId = null, ?string $handle = null): UserGroup
    {
        $provided = array_filter([
            'groupId' => $groupId !== null,
            'handle' => $handle !== null,
        ]);

        throw_unless(count($provided) === 1, \InvalidArgumentException::class, 'Provide exactly one of groupId or handle.');

        $group = $groupId !== null
            ? $this->userGroupsService->getGroupById($groupId)
            : $this->userGroupsService->getGroupByHandle((string) $handle);

        $identifier = $groupId !== null ? "ID {$groupId}" : "handle '{$handle}'";
        throw_unless($group instanceof UserGroup, \InvalidArgumentException::class, "User group with {$identifier} not found.");

        return $group;
    }
}
