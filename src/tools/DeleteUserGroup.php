<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\CmsEdition;
use happycog\craftmcp\actions\FormatUserGroup;
use happycog\craftmcp\actions\ResolveUserGroup;

class DeleteUserGroup
{
    public function __construct(
        protected FormatUserGroup $formatUserGroup,
        protected ResolveUserGroup $resolveUserGroup,
    ) {
    }

    /**
     * Delete a Craft user group by ID or handle.
     *
     * User groups require Craft Pro. Provide exactly one identifier.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User group ID to delete. */
        ?int $groupId = null,

        /** User group handle to delete. */
        ?string $handle = null,
    ): array
    {
        throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Managing user groups requires Craft Pro.');

        $group = ($this->resolveUserGroup)($groupId, $handle);

        $response = [
            '_notes' => 'The user group was successfully deleted.',
            ...($this->formatUserGroup)($group),
        ];

        throw_unless(\Craft::$app->getUserGroups()->deleteGroup($group), 'Failed to delete user group.');

        return $response;
    }
}
