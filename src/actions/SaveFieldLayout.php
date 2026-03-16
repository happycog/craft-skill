<?php

namespace happycog\craftmcp\actions;

use craft\elements\Address;
use craft\elements\User;
use craft\models\FieldLayout;
use craft\services\Fields;

class SaveFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected ClearFieldLayoutCache $clearFieldLayoutCache,
        protected NormalizeAddressFieldLayoutForSave $normalizeAddressFieldLayoutForSave,
        protected NormalizeUserFieldLayoutForSave $normalizeUserFieldLayoutForSave,
    ) {
    }

    public function __invoke(FieldLayout $fieldLayout): bool
    {
        if ($fieldLayout->type === Address::class) {
            $addressFieldLayout = ($this->normalizeAddressFieldLayoutForSave)($fieldLayout);

            ($this->clearFieldLayoutCache)();
            return $this->fieldsService->saveLayout($addressFieldLayout);
        }

        if ($fieldLayout->type === User::class) {
            $userFieldLayout = ($this->normalizeUserFieldLayoutForSave)($fieldLayout);

            ($this->clearFieldLayoutCache)();
            return $this->fieldsService->saveLayout($userFieldLayout);
        }

        return $this->fieldsService->saveLayout($fieldLayout);
    }
}
