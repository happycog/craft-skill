<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\services\Elements;
use craft\services\Users;
use happycog\craftmcp\actions\FormatUser;
use happycog\craftmcp\actions\ResolveUserGroupIds;
use happycog\craftmcp\actions\SaveUserPermissions;

class CreateUser
{
    public function __construct(
        protected Elements $elementsService,
        protected FormatUser $formatUser,
        protected ResolveUserGroupIds $resolveUserGroupIds,
        protected SaveUserPermissions $saveUserPermissions,
        protected Users $usersService,
    ) {
    }

    /**
     * Create a Craft user with native attributes, optional group assignments, direct permissions, and custom fields.
     *
     * Group assignment requires Craft Team or Pro. Direct user permissions require Craft Pro.
     * Custom permission names are supported and will be stored directly so they can be discovered later.
     *
     * @param array<string, mixed> $fields
     * @param list<int>|null $groupIds
     * @param list<string>|null $groupHandles
     * @param list<string>|null $permissions
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User email address. */
        string $email,

        /** Username to assign. Defaults to the email address. */
        ?string $username = null,

        /** Initial password for the user. */
        ?string $newPassword = null,

        /** Full name value. */
        ?string $fullName = null,

        /** First name value. */
        ?string $firstName = null,

        /** Last name value. */
        ?string $lastName = null,

        /** Whether the user should be an admin. */
        bool $admin = false,

        /** Whether the user should be active. */
        bool $active = true,

        /** Whether the user should be pending. */
        bool $pending = false,

        /** Whether the user should be suspended. */
        bool $suspended = false,

        /** Whether the user should be locked. */
        bool $locked = false,

        /** Affiliated site ID for the user. */
        ?int $affiliatedSiteId = null,

        /** User group IDs to assign. Requires Craft Team or Pro. */
        ?array $groupIds = null,

        /** User group handles to assign. Requires Craft Team or Pro. */
        ?array $groupHandles = null,

        /** Direct user permissions to assign. Requires Craft Pro. Custom names are allowed. */
        ?array $permissions = null,

        /** Custom field values from the global user field layout, keyed by field handle. */
        array $fields = [],
    ): array {
        throw_unless($this->usersService->canCreateUsers(), \InvalidArgumentException::class, 'The current Craft edition has reached its user limit, so an additional user cannot be created.');

        $user = new User();
        $user->email = $email;
        $user->username = $username ?? $email;
        $user->admin = $admin;
        $user->active = $active;
        $user->pending = $pending;
        $user->suspended = $suspended;
        $user->locked = $locked;
        $user->affiliatedSiteId = $affiliatedSiteId;
        $user->fullName = $fullName;
        $user->firstName = $firstName;
        $user->lastName = $lastName;

        if ($newPassword !== null) {
            $user->newPassword = $newPassword;
            $user->setScenario(User::SCENARIO_REGISTRATION);
        }

        if ($fields !== []) {
            $user->setFieldValues($fields);
        }

        throw_unless($this->elementsService->saveElement($user, false), 'Failed to save user: ' . implode(', ', $user->getFirstErrors()));

        $resolvedGroupIds = ($this->resolveUserGroupIds)($groupIds, $groupHandles);
        if ($resolvedGroupIds !== []) {
            throw_unless(Craft::$app->edition->value >= CmsEdition::Team->value, \InvalidArgumentException::class, 'Assigning users to groups requires Craft Team or Craft Pro.');
            throw_unless($this->usersService->assignUserToGroups((int) $user->id, $resolvedGroupIds), 'Failed to assign user groups.');
        }

        if ($permissions !== null && $user->id !== null) {
            throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Assigning direct user permissions requires Craft Pro.');
            throw_unless(($this->saveUserPermissions)($user->id, $permissions), 'Failed to save user permissions.');
        }

        return [
            '_notes' => 'The user was successfully created.',
            ...($this->formatUser)($user),
        ];
    }
}
