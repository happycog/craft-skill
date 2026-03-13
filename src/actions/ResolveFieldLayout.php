<?php

namespace happycog\craftmcp\actions;

use craft\elements\Address;
use craft\elements\User;
use craft\models\FieldLayout;
use craft\services\Fields;
use happycog\craftmcp\tools\GetAddressFieldLayout;
use happycog\craftmcp\tools\GetUserFieldLayout;

class ResolveFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected ResolvePersistedAddressFieldLayout $resolvePersistedAddressFieldLayout,
        protected ResolvePersistedUserFieldLayout $resolvePersistedUserFieldLayout,
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

        $userLayout = ($this->resolvePersistedUserFieldLayout)();

        if ($fieldLayoutId === GetUserFieldLayout::PLACEHOLDER_ID && $userLayout->type === User::class) {
            return $userLayout;
        }

        return null;
    }
}
