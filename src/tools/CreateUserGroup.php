<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\CmsEdition;
use craft\models\UserGroup;
use craft\services\UserGroups;
use craft\helpers\StringHelper;
use happycog\craftmcp\actions\FormatUserGroup;
use happycog\craftmcp\actions\SaveUserGroupPermissions;

class CreateUserGroup
{
    public function __construct(
        protected FormatUserGroup $formatUserGroup,
        protected SaveUserGroupPermissions $saveUserGroupPermissions,
        protected UserGroups $userGroupsService,
    ) {
    }

    /**
     * Create a Craft user group and optionally assign permissions.
     *
     * User groups require Craft Pro. Permission names are normalized to lowercase,
     * and arbitrary custom permission names are supported.
     *
     * @param list<string>|null $permissions
     * @return array<string, mixed>
     */
    public function __invoke(
        /** Human-readable group name. */
        string $name,

        /** Machine-readable group handle. Auto-generated from the name if omitted. */
        ?string $handle = null,

        /** Optional group description. */
        ?string $description = null,

        /** Permissions to assign, including custom permission names. */
        ?array $permissions = null,
    ): array {
        throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Managing user groups requires Craft Pro.');

        $group = new UserGroup();
        $group->name = $name;
        $group->handle = $handle ?? StringHelper::toHandle($name);
        $group->description = $description;

        throw_unless($this->userGroupsService->saveGroup($group), 'Failed to save user group: ' . implode(', ', $group->getFirstErrors()));

        if ($permissions !== null) {
            throw_unless(($this->saveUserGroupPermissions)($group, $permissions), 'Failed to save group permissions.');
        }

        return [
            '_notes' => 'The user group was successfully created.',
            ...($this->formatUserGroup)($group),
        ];
    }
}
