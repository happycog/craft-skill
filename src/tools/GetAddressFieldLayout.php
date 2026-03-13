<?php

namespace happycog\craftmcp\tools;

use craft\elements\Address;
use craft\helpers\UrlHelper;
use happycog\craftmcp\actions\GetFreshAddressFieldLayout;

class GetAddressFieldLayout
{
    public const PLACEHOLDER_ID = 0;

    public function __construct(
        protected GetFieldLayout $getFieldLayout,
        protected GetFreshAddressFieldLayout $getFreshAddressFieldLayout,
    ) {
    }

    /**
     * Get the single global field layout used by all Address elements.
     *
     * Craft only has one address field layout, and it applies to user-owned addresses
     * as well as addresses stored in custom `Addresses` fields. Use this tool to discover
     * the layout ID before modifying it with the existing field layout tools.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $fieldLayout = ($this->getFreshAddressFieldLayout)();
        $formattedFieldLayout = $this->getFieldLayout->formatFieldLayout($fieldLayout);
        $formattedFieldLayout['id'] = self::PLACEHOLDER_ID;

        return [
            '_notes' => 'Retrieved the global address field layout.',
            'fieldLayout' => $formattedFieldLayout,
            'settingsUrl' => UrlHelper::cpUrl('settings/addresses'),
            'elementType' => Address::class,
        ];
    }
}
