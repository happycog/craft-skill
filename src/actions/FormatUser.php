<?php

namespace happycog\craftmcp\actions;

use craft\elements\User;
use craft\models\UserGroup;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\helpers\UrlHelper;

class FormatUser
{
    public function __construct(
        protected UserPermissions $userPermissions,
        protected Users $usersService,
        protected Sites $sitesService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $user): array
    {
        $groups = $user->getGroups();
        $groupPermissions = $user->id !== null ? $this->userPermissions->getGroupPermissionsByUserId($user->id) : [];
        $permissions = $user->id !== null ? $this->userPermissions->getPermissionsByUserId($user->id) : [];
        $site = $user->affiliatedSiteId !== null ? $this->sitesService->getSiteById($user->affiliatedSiteId) : null;

        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'username' => $user->username,
            'email' => $user->email,
            'fullName' => $user->fullName,
            'friendlyName' => $user->getFriendlyName(),
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'status' => $user->getStatus(),
            'active' => $user->active,
            'pending' => $user->pending,
            'locked' => $user->locked,
            'suspended' => $user->suspended,
            'admin' => $user->admin,
            'enabled' => $user->enabled,
            'photoId' => $user->photoId,
            'preferredLanguage' => $user->getPreferredLanguage(),
            'preferredLocale' => $user->getPreferredLocale(),
            'affiliatedSite' => $site !== null ? [
                'id' => $site->id,
                'name' => $site->name,
                'handle' => $site->handle,
            ] : null,
            'fieldLayoutId' => $user->getFieldLayout()?->id,
            'groups' => array_map(fn(UserGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'handle' => $group->handle,
                'uid' => $group->uid,
            ], $groups),
            'permissions' => $permissions,
            'groupPermissions' => $groupPermissions,
            'directPermissions' => array_values(array_diff($permissions, $groupPermissions)),
            'customFields' => $user->getSerializedFieldValues(),
            'cpEditUrl' => $user->id !== null ? UrlHelper::cpUrl("users/{$user->id}") : null,
            'settingsUrl' => UrlHelper::cpUrl('settings/users'),
            'dateCreated' => $user->dateCreated?->format('c'),
            'dateUpdated' => $user->dateUpdated?->format('c'),
        ];
    }
}
