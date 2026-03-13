<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\CmsEdition;
use craft\services\UserGroups;
use happycog\craftmcp\actions\FormatUserGroup;
use happycog\craftmcp\actions\ResolveUserGroup;
use happycog\craftmcp\actions\SaveUserGroupPermissions;

class UpdateUserGroup
{
    public function __construct(
        protected FormatUserGroup $formatUserGroup,
        protected ResolveUserGroup $resolveUserGroup,
        protected SaveUserGroupPermissions $saveUserGroupPermissions,
        protected UserGroups $userGroupsService,
    ) {
    }

    /**
     * Update a Craft user group by ID or handle.
     *
     * User groups require Craft Pro. This can rename the group, update its description,
     * and replace its assigned permissions.
     *
     * @param list<string>|null $permissions
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User group ID to update. */
        ?int $groupId = null,

        /** User group handle to update. */
        ?string $handle = null,

        /** New display name. */
        ?string $newName = null,

        /** New handle. */
        ?string $newHandle = null,

        /** New description. */
        ?string $description = null,

        /** Replacement permissions, including custom permission names. */
        ?array $permissions = null,
    ): array {
        throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Managing user groups requires Craft Pro.');

        $group = ($this->resolveUserGroup)($groupId, $handle);

        $newName !== null && $group->name = $newName;
        $newHandle !== null && $group->handle = $newHandle;
        $description !== null && $group->description = $description;

        throw_unless($this->userGroupsService->saveGroup($group), 'Failed to save user group: ' . implode(', ', $group->getFirstErrors()));

        if ($permissions !== null) {
            throw_unless(($this->saveUserGroupPermissions)($group, $permissions), 'Failed to save group permissions.');
        }

        return [
            '_notes' => 'The user group was successfully updated.',
            ...($this->formatUserGroup)($group),
        ];
    }
}
