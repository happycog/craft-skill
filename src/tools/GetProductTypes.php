<?php

namespace happycog\craftmcp\tools;

use craft\commerce\Plugin as Commerce;

class GetProductTypes
{
    /**
     * List all available Commerce product types.
     *
     * Product types define the structure and fields for products in Craft Commerce,
     * similar to how entry types define structure for entries. Use this to discover
     * available product types before creating or searching for products.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $productTypes = $commerce->getProductTypes()->getAllProductTypes();

        $types = [];
        foreach ($productTypes as $productType) {
            $types[] = [
                'id' => $productType->id,
                'name' => $productType->name,
                'handle' => $productType->handle,
                'hasDimensions' => $productType->hasDimensions,
                'hasVariantTitleField' => $productType->hasVariantTitleField,
                'maxVariants' => $productType->maxVariants,
            ];
        }

        return [
            '_notes' => 'Retrieved all Commerce product types.',
            'productTypes' => $types,
        ];
    }
}
