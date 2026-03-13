<?php

namespace happycog\craftmcp\actions;

use craft\elements\Address;
use craft\models\FieldLayout;
use craft\services\Fields;

class SaveFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected ClearFieldLayoutCache $clearFieldLayoutCache,
        protected NormalizeAddressFieldLayoutForSave $normalizeAddressFieldLayoutForSave,
    ) {
    }

    public function __invoke(FieldLayout $fieldLayout): bool
    {
        if ($fieldLayout->type === Address::class) {
            $addressFieldLayout = ($this->normalizeAddressFieldLayoutForSave)($fieldLayout);

            ($this->clearFieldLayoutCache)();
            return $this->fieldsService->saveLayout($addressFieldLayout);
        }

        return $this->fieldsService->saveLayout($fieldLayout);
    }
}
