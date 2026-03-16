<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\models\ProductType;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use happycog\craftmcp\exceptions\ModelSaveException;

class UpdateProductType
{
    /**
     * Update an existing Commerce product type. Only the provided properties are updated;
     * all others remain unchanged.
     *
     * Allows modification of product type configuration including name, handle, title field
     * settings, variant settings, field layouts, and per-site URL configuration.
     *
     * After updating the product type always link the user back to the product type settings
     * in the Craft control panel so they can review the changes in the context of the Craft UI.
     *
     * @param 'custom'|'language'|'none'|'site'|'siteGroup'|null $productTitleTranslationMethod
     * @param 'custom'|'language'|'none'|'site'|'siteGroup'|null $variantTitleTranslationMethod
     * @param 'custom'|'language'|'none'|'site'|'siteGroup'|null $slugTranslationMethod
     * @param 'beginning'|'end'|null $defaultPlacement
     * @param array<int, array{siteId: int, enabledByDefault?: bool, hasUrls?: bool, uriFormat?: string, template?: string}>|null $siteSettings
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The ID of the product type to update */
        int $productTypeId,

        /** The display name for the product type */
        ?string $name = null,

        /** The product type handle (machine-readable name) */
        ?string $handle = null,

        /** Whether products have a title field. If set to false, productTitleFormat is required. */
        ?bool $hasProductTitleField = null,

        /** Auto-generated title format for products when hasProductTitleField is false. */
        ?string $productTitleFormat = null,

        /** How product titles are translated: none, site, language, or custom. */
        ?string $productTitleTranslationMethod = null,

        /** Translation key format for custom product title translation. */
        ?string $productTitleTranslationKeyFormat = null,

        /** Whether variants have a title field. If set to false, variantTitleFormat is required. */
        ?bool $hasVariantTitleField = null,

        /** Auto-generated title format for variants when hasVariantTitleField is false. */
        ?string $variantTitleFormat = null,

        /** How variant titles are translated: none, site, language, or custom. */
        ?string $variantTitleTranslationMethod = null,

        /** Translation key format for custom variant title translation. */
        ?string $variantTitleTranslationKeyFormat = null,

        /** Whether to show the slug field in the admin UI. */
        ?bool $showSlugField = null,

        /** How slugs are translated: none, site, language, or custom. */
        ?string $slugTranslationMethod = null,

        /** Translation key format for custom slug translation. */
        ?string $slugTranslationKeyFormat = null,

        /** SKU format pattern. If set, SKUs are auto-generated. */
        ?string $skuFormat = null,

        /** Description format for the variant description. */
        ?string $descriptionFormat = null,

        /** Product page template path. */
        ?string $template = null,

        /** Whether products of this type track dimensions. */
        ?bool $hasDimensions = null,

        /** Maximum number of variants per product. Null for unlimited. */
        ?int $maxVariants = null,

        /** Whether to enable entry versioning for products. */
        ?bool $enableVersioning = null,

        /** Whether products use a hierarchical structure. */
        ?bool $isStructure = null,

        /** Maximum hierarchy levels (only for structure product types). */
        ?int $maxLevels = null,

        /** Where new products are placed by default (only for structure product types). */
        ?string $defaultPlacement = null,

        /** Field layout ID for product-level fields. */
        ?int $fieldLayoutId = null,

        /** Field layout ID for variant-level fields. */
        ?int $variantFieldLayoutId = null,

        /**
         * Site-specific settings. Replaces existing site settings if provided.
         * Each array entry contains:
         * - siteId: Site ID (required)
         * - enabledByDefault: Enable products by default for this site (optional)
         * - hasUrls: Whether products have URLs on this site (optional)
         * - uriFormat: URI format pattern (optional)
         * - template: Template path for rendering products (optional)
         */
        ?array $siteSettings = null,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $productType = $commerce->getProductTypes()->getProductTypeById($productTypeId);

        throw_unless(
            $productType instanceof ProductType,
            \InvalidArgumentException::class,
            "Product type with ID {$productTypeId} not found",
        );

        // Update basic properties only if provided
        if ($name !== null) {
            $productType->name = $name;
        }
        if ($handle !== null) {
            $productType->handle = $handle;
        }
        if ($hasProductTitleField !== null) {
            $productType->hasProductTitleField = $hasProductTitleField;
        }
        if ($productTitleFormat !== null) {
            $productType->productTitleFormat = $productTitleFormat;
        }
        if ($productTitleTranslationMethod !== null) {
            $productType->productTitleTranslationMethod = $this->getTranslationMethodConstant($productTitleTranslationMethod);
        }
        if ($productTitleTranslationKeyFormat !== null) {
            $productType->productTitleTranslationKeyFormat = $productTitleTranslationKeyFormat;
        }
        if ($hasVariantTitleField !== null) {
            $productType->hasVariantTitleField = $hasVariantTitleField;
        }
        if ($variantTitleFormat !== null) {
            $productType->variantTitleFormat = $variantTitleFormat;
        }
        if ($variantTitleTranslationMethod !== null) {
            $productType->variantTitleTranslationMethod = $this->getTranslationMethodConstant($variantTitleTranslationMethod);
        }
        if ($variantTitleTranslationKeyFormat !== null) {
            $productType->variantTitleTranslationKeyFormat = $variantTitleTranslationKeyFormat;
        }
        if ($showSlugField !== null) {
            $productType->showSlugField = $showSlugField;
        }
        if ($slugTranslationMethod !== null) {
            $productType->slugTranslationMethod = $this->getTranslationMethodConstant($slugTranslationMethod);
        }
        if ($slugTranslationKeyFormat !== null) {
            $productType->slugTranslationKeyFormat = $slugTranslationKeyFormat;
        }
        if ($skuFormat !== null) {
            $productType->skuFormat = $skuFormat;
        }
        if ($descriptionFormat !== null) {
            $productType->descriptionFormat = $descriptionFormat;
        }
        if ($template !== null) {
            $productType->template = $template;
        }
        if ($hasDimensions !== null) {
            $productType->hasDimensions = $hasDimensions;
        }
        if ($maxVariants !== null) {
            $productType->maxVariants = $maxVariants;
        }
        if ($enableVersioning !== null) {
            $productType->enableVersioning = $enableVersioning;
        }
        if ($isStructure !== null) {
            $productType->isStructure = $isStructure;
        }
        if ($maxLevels !== null && $productType->isStructure) {
            $productType->maxLevels = $maxLevels > 0 ? $maxLevels : null;
        }
        if ($defaultPlacement !== null && $productType->isStructure) {
            $productType->defaultPlacement = $this->getDefaultPlacement($defaultPlacement);
        }

        // Validate title format requirements after updates
        throw_if(
            !$productType->hasProductTitleField && empty($productType->productTitleFormat),
            \InvalidArgumentException::class,
            "Product title format is required when hasProductTitleField is false.",
        );
        throw_if(
            !$productType->hasVariantTitleField && empty($productType->variantTitleFormat),
            \InvalidArgumentException::class,
            "Variant title format is required when hasVariantTitleField is false.",
        );

        // Update field layouts if provided
        if ($fieldLayoutId !== null) {
            $fieldLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);
            throw_unless($fieldLayout, \InvalidArgumentException::class, "Field layout with ID {$fieldLayoutId} not found");
            $productType->fieldLayoutId = $fieldLayoutId;
        }
        if ($variantFieldLayoutId !== null) {
            $variantFieldLayout = Craft::$app->getFields()->getLayoutById($variantFieldLayoutId);
            throw_unless($variantFieldLayout, \InvalidArgumentException::class, "Variant field layout with ID {$variantFieldLayoutId} not found");
            $productType->variantFieldLayoutId = $variantFieldLayoutId;
        }

        // Update site settings if provided
        if ($siteSettings !== null) {
            $siteSettingsObjects = [];
            foreach ($siteSettings as $siteData) {
                assert(is_array($siteData), 'Site data must be an array');
                assert(is_int($siteData['siteId']), 'Site ID must be an integer');

                $siteId = $siteData['siteId'];
                $site = Craft::$app->getSites()->getSiteById($siteId);
                throw_unless($site, "Site with ID {$siteId} not found");

                $siteSettingsObjects[$siteId] = new ProductTypeSite([
                    'productTypeId' => $productType->id,
                    'siteId' => $siteId,
                    'enabledByDefault' => $siteData['enabledByDefault'] ?? true,
                    'hasUrls' => $siteData['hasUrls'] ?? false,
                    'uriFormat' => $siteData['uriFormat'] ?? null,
                    'template' => $siteData['template'] ?? null,
                ]);
            }

            $productType->setSiteSettings($siteSettingsObjects);
        }

        // Save the product type
        throw_unless(
            $commerce->getProductTypes()->saveProductType($productType),
            ModelSaveException::class,
            $productType,
        );

        return [
            '_notes' => 'The product type was successfully updated.',
            'id' => $productType->id,
            'name' => $productType->name,
            'handle' => $productType->handle,
            'fieldLayoutId' => $productType->fieldLayoutId,
            'variantFieldLayoutId' => $productType->variantFieldLayoutId,
            'hasProductTitleField' => $productType->hasProductTitleField,
            'productTitleFormat' => $productType->productTitleFormat,
            'hasVariantTitleField' => $productType->hasVariantTitleField,
            'variantTitleFormat' => $productType->variantTitleFormat,
            'skuFormat' => $productType->skuFormat,
            'hasDimensions' => $productType->hasDimensions,
            'maxVariants' => $productType->maxVariants,
            'enableVersioning' => $productType->enableVersioning,
            'editUrl' => $productType->getCpEditUrl(),
            'editVariantUrl' => $productType->getCpEditVariantUrl(),
        ];
    }

    /**
     * @return 'custom'|'language'|'none'|'site'|'siteGroup'
     */
    private function getTranslationMethodConstant(string $method): string
    {
        $methodMap = [
            'none' => \craft\base\Field::TRANSLATION_METHOD_NONE,
            'site' => \craft\base\Field::TRANSLATION_METHOD_SITE,
            'siteGroup' => \craft\base\Field::TRANSLATION_METHOD_SITE_GROUP,
            'language' => \craft\base\Field::TRANSLATION_METHOD_LANGUAGE,
            'custom' => \craft\base\Field::TRANSLATION_METHOD_CUSTOM,
        ];

        throw_unless(
            isset($methodMap[$method]),
            \InvalidArgumentException::class,
            "Invalid translation method '{$method}'. Must be one of: " . implode(', ', array_keys($methodMap)),
        );

        return $methodMap[$method];
    }

    /**
     * @return 'beginning'|'end'
     */
    private function getDefaultPlacement(string $defaultPlacement): string
    {
        throw_unless(
            in_array($defaultPlacement, ['beginning', 'end'], true),
            \InvalidArgumentException::class,
            'defaultPlacement must be "beginning" or "end"',
        );

        return $defaultPlacement;
    }
}
