<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\services\Elements;
use craft\services\Users;
use happycog\craftmcp\actions\FormatUser;
use happycog\craftmcp\actions\ResolveUser;
use happycog\craftmcp\actions\ResolveUserGroupIds;
use happycog\craftmcp\actions\SaveUserPermissions;

class UpdateUser
{
    public function __construct(
        protected Elements $elementsService,
        protected FormatUser $formatUser,
        protected ResolveUser $resolveUser,
        protected ResolveUserGroupIds $resolveUserGroupIds,
        protected SaveUserPermissions $saveUserPermissions,
        protected Users $usersService,
    ) {
    }

    /**
     * Update a Craft user by ID, email, or username.
     *
     * Provide exactly one identifier. This can update native attributes, activation state, group membership,
     * direct permissions, and custom field values.
     *
     * @param array<string, mixed> $fields
     * @param list<int>|null $groupIds
     * @param list<string>|null $groupHandles
     * @param list<string>|null $permissions
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User ID to update. */
        ?int $userId = null,

        /** Resolve the user by current email address. */
        ?string $email = null,

        /** Resolve the user by current username. */
        ?string $username = null,

        /** New email address to set. */
        ?string $newEmail = null,

        /** New username to set. */
        ?string $newUsername = null,

        /** New password to set. */
        ?string $newPassword = null,

        /** Updated full name. */
        ?string $fullName = null,

        /** Updated first name. */
        ?string $firstName = null,

        /** Updated last name. */
        ?string $lastName = null,

        /** Updated admin flag. */
        ?bool $admin = null,

        /** Activate or deactivate the user. */
        ?bool $active = null,

        /** Updated pending flag. */
        ?bool $pending = null,

        /** Suspend or unsuspend the user. */
        ?bool $suspended = null,

        /** Set to false to unlock the user. */
        ?bool $locked = null,

        /** Updated affiliated site ID. */
        ?int $affiliatedSiteId = null,

        /** Replacement user group IDs. Requires Craft Team or Pro. */
        ?array $groupIds = null,

        /** Replacement user group handles. Requires Craft Team or Pro. */
        ?array $groupHandles = null,

        /** Replacement direct user permissions. Requires Craft Pro. Custom names are allowed. */
        ?array $permissions = null,

        /** Updated custom field values keyed by field handle. */
        array $fields = [],
    ): array {
        $user = ($this->resolveUser)($userId, $email, $username);
        $wasActive = $user->active;
        $wasLocked = $user->locked;
        $wasSuspended = $user->suspended;

        $newEmail !== null && $user->email = $newEmail;
        $newUsername !== null && $user->username = $newUsername;
        $fullName !== null && $user->fullName = $fullName;
        $firstName !== null && $user->firstName = $firstName;
        $lastName !== null && $user->lastName = $lastName;
        $admin !== null && $user->admin = $admin;
        if ($pending !== null) {
            $user->pending = $pending;
        }

        if ($locked !== null) {
            $user->locked = $locked;
        }

        $affiliatedSiteId !== null && $user->affiliatedSiteId = $affiliatedSiteId;

        if ($newPassword !== null) {
            $user->newPassword = $newPassword;
            $user->setScenario(User::SCENARIO_PASSWORD);
        }

        if ($fields !== []) {
            $user->setFieldValues($fields);
        }

        throw_unless($this->elementsService->saveElement($user, false), 'Failed to save user: ' . implode(', ', $user->getFirstErrors()));

        if ($locked === false && $wasLocked) {
            $this->usersService->unlockUser($user);
        }

        if ($suspended === true && !$wasSuspended) {
            $this->usersService->suspendUser($user);
        } elseif ($suspended === false && $wasSuspended) {
            $this->usersService->unsuspendUser($user);
        }

        if ($active === true && !$wasActive) {
            $this->usersService->activateUser($user);
        } elseif ($active === false && $wasActive) {
            $this->usersService->deactivateUser($user);
        }

        if ($groupIds !== null || $groupHandles !== null) {
            throw_unless(Craft::$app->edition->value >= CmsEdition::Team->value, \InvalidArgumentException::class, 'Assigning users to groups requires Craft Team or Craft Pro.');
            $resolvedGroupIds = ($this->resolveUserGroupIds)($groupIds, $groupHandles);
            throw_unless($this->usersService->assignUserToGroups((int) $user->id, $resolvedGroupIds), 'Failed to update user groups.');
        }

        if ($permissions !== null && $user->id !== null) {
            throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Assigning direct user permissions requires Craft Pro.');
            throw_unless(($this->saveUserPermissions)($user->id, $permissions), 'Failed to save user permissions.');
        }

        return [
            '_notes' => 'The user was successfully updated.',
            ...($this->formatUser)($user),
        ];
    }
}
