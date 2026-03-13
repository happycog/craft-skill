<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\models\UserGroup;
use craft\services\UserGroups;
use Illuminate\Support\Collection;
use happycog\craftmcp\actions\FormatUser;

class GetUsers
{
    public function __construct(
        protected FormatUser $formatUser,
        protected UserGroups $userGroupsService,
    ) {
    }

    /**
     * List Craft users with optional filters for search text, identity fields, status, and group membership.
     *
     * This returns formatted user records including groups, permissions, direct permissions, and custom field values.
     * Group filtering requires Craft Team or Pro because user groups are not available on Solo.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** Search text passed to Craft's user element query. */
        ?string $query = null,

        /** Exact email filter. */
        ?string $email = null,

        /** Exact username filter. */
        ?string $username = null,

        /** Filter by user group ID. Requires Craft Team or Pro. */
        ?int $groupId = null,

        /** Filter by user group handle. Requires Craft Team or Pro. */
        ?string $groupHandle = null,

        /** Craft user status such as active, pending, suspended, locked, or inactive. */
        ?string $status = null,

        /** Maximum number of users to return. */
        int $limit = 25,
    ): array {
        $userQuery = User::find()
            ->status(null)
            ->site('*')
            ->limit($limit);

        $notes = [];

        if ($query !== null) {
            $userQuery->search($query);
            $notes[] = "query '{$query}'";
        }

        if ($email !== null) {
            $userQuery->email($email);
            $notes[] = "email {$email}";
        }

        if ($username !== null) {
            $userQuery->username($username);
            $notes[] = "username {$username}";
        }

        if ($groupId !== null || $groupHandle !== null) {
            throw_unless($groupId === null || $groupHandle === null, \InvalidArgumentException::class, 'Provide either groupId or groupHandle, not both.');
            throw_unless(Craft::$app->edition->value >= CmsEdition::Team->value, \InvalidArgumentException::class, 'Filtering users by group requires Craft Team or Craft Pro.');
            $group = $groupId !== null
                ? $this->userGroupsService->getGroupById($groupId)
                : $this->userGroupsService->getGroupByHandle((string) $groupHandle);

            throw_unless($group instanceof UserGroup, \InvalidArgumentException::class, 'User group filter not found.');
            $userQuery->group($group);
            $notes[] = "group {$group->handle}";
        }

        if ($status !== null) {
            $userQuery->status($status);
            $notes[] = "status {$status}";
        }

        return [
            '_notes' => $notes === []
                ? 'The following users were found.'
                : 'The following users were found matching ' . implode(' and ', $notes) . '.',
            'results' => Collection::make($userQuery->all())
                ->map(fn(User $user) => ($this->formatUser)($user))
                ->values()
                ->all(),
        ];
    }
}
