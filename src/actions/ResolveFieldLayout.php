<?php

namespace happycog\craftmcp\actions;

use craft\elements\Address;
use craft\models\FieldLayout;
use craft\services\Fields;
use happycog\craftmcp\tools\GetAddressFieldLayout;

class ResolveFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected ResolvePersistedAddressFieldLayout $resolvePersistedAddressFieldLayout,
    ) {
    }

    public function __invoke(int $fieldLayoutId): ?FieldLayout
    {
        $fieldLayout = $this->fieldsService->getLayoutById($fieldLayoutId);

        if ($fieldLayout instanceof FieldLayout) {
            return $fieldLayout;
        }

        $addressLayout = ($this->resolvePersistedAddressFieldLayout)();

        if ($fieldLayoutId === GetAddressFieldLayout::PLACEHOLDER_ID && $addressLayout->type === Address::class) {
            return $addressLayout;
        }

        return null;
    }
}
