<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\CmsEdition;
use Illuminate\Support\Collection;
use happycog\craftmcp\actions\FormatUserGroup;

class GetUserGroups
{
    public function __construct(
        protected FormatUserGroup $formatUserGroup,
    ) {
    }

    /**
     * List Craft user groups.
     *
     * User groups and group permissions require Craft Pro. The response includes each group's
     * permissions, user count, and control-panel edit URL.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        throw_unless(Craft::$app->edition->value >= CmsEdition::Pro->value, \InvalidArgumentException::class, 'Managing user groups requires Craft Pro.');

        $groups = Craft::$app->getUserGroups()->getAllGroups();

        return [
            '_notes' => 'The following user groups were found.',
            'results' => Collection::make($groups)
                ->map(fn($group) => ($this->formatUserGroup)($group))
                ->values()
                ->all(),
        ];
    }
}
