<?php

namespace happycog\craftmcp\tools;

use craft\elements\User;
use craft\helpers\UrlHelper;
use happycog\craftmcp\actions\GetFreshUserFieldLayout;

class GetUserFieldLayout
{
    public const PLACEHOLDER_ID = -100;

    public function __construct(
        protected GetFieldLayout $getFieldLayout,
        protected GetFreshUserFieldLayout $getFreshUserFieldLayout,
    ) {
    }

    /**
     * Retrieve the single global field layout used by Craft users.
     *
     * The returned field layout ID is a stable placeholder value that can be passed to the generic
     * field-layout mutation tools when modifying the global user layout.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $fieldLayout = ($this->getFreshUserFieldLayout)();
        $formattedFieldLayout = $this->getFieldLayout->formatFieldLayout($fieldLayout);
        $formattedFieldLayout['id'] = self::PLACEHOLDER_ID;

        return [
            '_notes' => 'Retrieved the global user field layout.',
            'fieldLayout' => $formattedFieldLayout,
            'settingsUrl' => UrlHelper::cpUrl('settings/users'),
            'elementType' => User::class,
        ];
    }
}
