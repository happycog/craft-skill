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
     * Returns each product type's configuration including field layout IDs, title field
     * settings, variant settings, and per-site URL configuration.
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
            $siteSettings = [];
            foreach ($productType->getSiteSettings() as $siteId => $siteSetting) {
                $siteSettings[] = [
                    'siteId' => (int) $siteId,
                    'hasUrls' => $siteSetting->hasUrls,
                    'uriFormat' => $siteSetting->uriFormat,
                    'template' => $siteSetting->template,
                    'enabledByDefault' => $siteSetting->enabledByDefault,
                ];
            }

            $types[] = [
                'id' => $productType->id,
                'name' => $productType->name,
                'handle' => $productType->handle,
                'fieldLayoutId' => $productType->fieldLayoutId,
                'variantFieldLayoutId' => $productType->variantFieldLayoutId,
                'hasDimensions' => $productType->hasDimensions,
                'hasProductTitleField' => $productType->hasProductTitleField,
                'productTitleFormat' => $productType->productTitleFormat,
                'hasVariantTitleField' => $productType->hasVariantTitleField,
                'variantTitleFormat' => $productType->variantTitleFormat,
                'skuFormat' => $productType->skuFormat,
                'maxVariants' => $productType->maxVariants,
                'siteSettings' => $siteSettings,
            ];
        }

        return [
            '_notes' => 'Retrieved all Commerce product types.',
            'productTypes' => $types,
        ];
    }
}
