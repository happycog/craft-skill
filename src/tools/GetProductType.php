<?php

namespace happycog\craftmcp\tools;

use craft\commerce\models\ProductType;
use craft\commerce\Plugin as Commerce;
use happycog\craftmcp\actions\FieldFormatter;

class GetProductType
{
    public function __construct(
        protected FieldFormatter $fieldFormatter,
    ) {
    }

    /**
     * Get detailed information about a single Commerce product type by ID.
     *
     * Returns comprehensive product type details including all configuration properties,
     * per-site URL settings, and full field information for both the product-level and
     * variant-level field layouts. Use this when you need the complete schema for a
     * product type. For an overview of all product types without field details, use
     * `product-types/list` instead.
     *
     * After retrieving product type information, you can use the product type ID to create
     * new products with `products/create`.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** ID of the product type to retrieve */
        int $productTypeId,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $productType = $commerce->getProductTypes()->getProductTypeById($productTypeId);

        throw_unless(
            $productType instanceof ProductType,
            \InvalidArgumentException::class,
            "Product type with ID {$productTypeId} not found",
        );

        // Format site settings
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

        // Format product-level field layout fields
        $productFields = [];
        $productFieldLayout = $productType->getFieldLayout();
        if ($productFieldLayout->id !== null) {
            $productFields = $this->fieldFormatter->formatFieldsForLayout($productFieldLayout);
        }

        // Format variant-level field layout fields
        $variantFields = [];
        $variantFieldLayout = $productType->getVariantFieldLayout();
        if ($variantFieldLayout->id !== null) {
            $variantFields = $this->fieldFormatter->formatFieldsForLayout($variantFieldLayout);
        }

        return [
            '_notes' => 'Retrieved product type details with field layouts.',
            'id' => $productType->id,
            'name' => $productType->name,
            'handle' => $productType->handle,
            'fieldLayoutId' => $productType->fieldLayoutId,
            'variantFieldLayoutId' => $productType->variantFieldLayoutId,
            'hasDimensions' => $productType->hasDimensions,
            'hasProductTitleField' => $productType->hasProductTitleField,
            'productTitleFormat' => $productType->productTitleFormat,
            'productTitleTranslationMethod' => $productType->productTitleTranslationMethod,
            'productTitleTranslationKeyFormat' => $productType->productTitleTranslationKeyFormat,
            'hasVariantTitleField' => $productType->hasVariantTitleField,
            'variantTitleFormat' => $productType->variantTitleFormat,
            'variantTitleTranslationMethod' => $productType->variantTitleTranslationMethod,
            'variantTitleTranslationKeyFormat' => $productType->variantTitleTranslationKeyFormat,
            'showSlugField' => $productType->showSlugField,
            'slugTranslationMethod' => $productType->slugTranslationMethod,
            'slugTranslationKeyFormat' => $productType->slugTranslationKeyFormat,
            'skuFormat' => $productType->skuFormat,
            'descriptionFormat' => $productType->descriptionFormat,
            'template' => $productType->template,
            'maxVariants' => $productType->maxVariants,
            'enableVersioning' => $productType->enableVersioning,
            'isStructure' => $productType->isStructure,
            'maxLevels' => $productType->isStructure ? $productType->maxLevels : null,
            'defaultPlacement' => $productType->isStructure ? $productType->defaultPlacement : null,
            'propagationMethod' => $productType->propagationMethod->value,
            'siteSettings' => $siteSettings,
            'productFields' => $productFields,
            'variantFields' => $variantFields,
            'editUrl' => $productType->getCpEditUrl(),
            'editVariantUrl' => $productType->getCpEditVariantUrl(),
        ];
    }
}
