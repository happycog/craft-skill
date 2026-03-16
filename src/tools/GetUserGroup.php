<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\CmsEdition;
use happycog\craftmcp\actions\FormatUserGroup;
use happycog\craftmcp\actions\ResolveUserGroup;

class GetUserGroup
{
    public function __construct(
        protected FormatUserGroup $formatUserGroup,
        protected ResolveUserGroup $resolveUserGroup,
    ) {
    }

    /**
     * Retrieve a single Craft user group by ID or handle.
     *
     * Provide exactly one identifier. User groups require Craft Pro.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** User group ID to look up. */
        ?int $groupId = null,

        /** User group handle to look up. */
        ?string $handle = null,
    ): array
    {
        throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Managing user groups requires Craft Pro.');

        $group = ($this->resolveUserGroup)($groupId, $handle);

        return [
            '_notes' => 'Retrieved user group details.',
            ...($this->formatUserGroup)($group),
        ];
    }
}
