<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\models\ProductType;
use craft\commerce\models\ProductTypeSite;
use craft\commerce\Plugin as Commerce;
use craft\helpers\StringHelper;
use happycog\craftmcp\exceptions\ModelSaveException;

class CreateProductType
{
    /**
     * Create a new Commerce product type. Product types define the structure, fields, and
     * variant configuration for products in Craft Commerce — similar to how sections and
     * entry types define structure for entries.
     *
     * Product types have two independent field layouts:
     * - Product-level fields (fieldLayoutId) for product attributes
     * - Variant-level fields (variantFieldLayoutId) for variant attributes
     *
     * Create field layouts first using `field-layouts/create`, then assign their IDs here.
     *
     * Supports multi-site installations with per-site URL settings. If no site settings are
     * provided, the product type will be enabled for all sites with default settings.
     *
     * After creating the product type always link the user back to the product type settings
     * in the Craft control panel so they can review and further configure the product type
     * in the context of the Craft UI.
     *
     * @param 'custom'|'language'|'none'|'site'|'siteGroup' $productTitleTranslationMethod
     * @param 'custom'|'language'|'none'|'site'|'siteGroup' $variantTitleTranslationMethod
     * @param 'custom'|'language'|'none'|'site'|'siteGroup' $slugTranslationMethod
     * @param 'beginning'|'end' $defaultPlacement
     * @param array<int, array{siteId: int, enabledByDefault?: bool, hasUrls?: bool, uriFormat?: string, template?: string}>|null $siteSettings
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The display name for the product type */
        string $name,

        /** The product type handle (machine-readable name). Auto-generated from name if not provided. */
        ?string $handle = null,

        /** Whether products have a title field. If false, productTitleFormat is required. */
        bool $hasProductTitleField = true,

        /** Auto-generated title format for products when hasProductTitleField is false. */
        ?string $productTitleFormat = null,

        /** How product titles are translated: none, site, language, or custom. */
        string $productTitleTranslationMethod = 'site',

        /** Translation key format for custom product title translation. */
        ?string $productTitleTranslationKeyFormat = null,

        /** Whether variants have a title field. If false, variantTitleFormat is required. */
        bool $hasVariantTitleField = true,

        /** Auto-generated title format for variants when hasVariantTitleField is false. */
        ?string $variantTitleFormat = null,

        /** How variant titles are translated: none, site, language, or custom. */
        string $variantTitleTranslationMethod = 'site',

        /** Translation key format for custom variant title translation. */
        ?string $variantTitleTranslationKeyFormat = null,

        /** Whether to show the slug field in the admin UI. */
        bool $showSlugField = true,

        /** How slugs are translated: none, site, language, or custom. */
        string $slugTranslationMethod = 'site',

        /** Translation key format for custom slug translation. */
        ?string $slugTranslationKeyFormat = null,

        /** SKU format pattern. If set, SKUs are auto-generated (e.g., "{product.slug}"). */
        ?string $skuFormat = null,

        /** Description format for the variant description (e.g., "{product.title} - {title}"). */
        string $descriptionFormat = '{product.title} - {title}',

        /** Product page template path (e.g., "shop/products/_product"). */
        ?string $template = null,

        /** Whether products of this type track dimensions (weight, height, length, width). */
        bool $hasDimensions = false,

        /** Maximum number of variants per product. Null for unlimited. */
        ?int $maxVariants = null,

        /** Whether to enable entry versioning for products. */
        bool $enableVersioning = false,

        /** Whether products use a hierarchical structure (like structure sections). */
        bool $isStructure = false,

        /** Maximum hierarchy levels (only for structure product types). Null for unlimited. */
        ?int $maxLevels = null,

        /** Where new products are placed by default (only for structure product types): "beginning" or "end". */
        string $defaultPlacement = 'end',

        /** Field layout ID for product-level fields. Create one with field-layouts/create first. */
        ?int $fieldLayoutId = null,

        /** Field layout ID for variant-level fields. Create one with field-layouts/create first. */
        ?int $variantFieldLayoutId = null,

        /**
         * Site-specific settings. If not provided, product type will be enabled for all sites.
         * Each array entry contains:
         * - siteId: Site ID (required)
         * - enabledByDefault: Enable products by default for this site (optional, default true)
         * - hasUrls: Whether products have URLs on this site (optional, default false)
         * - uriFormat: URI format pattern, e.g., "shop/products/{slug}" (optional)
         * - template: Template path for rendering products (optional)
         */
        ?array $siteSettings = null,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        // Validate title format requirements
        throw_if(
            !$hasProductTitleField && empty($productTitleFormat),
            \InvalidArgumentException::class,
            "If 'hasProductTitleField' is false, 'productTitleFormat' must be set to define how product titles are automatically generated.",
        );
        throw_if(
            !$hasVariantTitleField && empty($variantTitleFormat),
            \InvalidArgumentException::class,
            "If 'hasVariantTitleField' is false, 'variantTitleFormat' must be set to define how variant titles are automatically generated.",
        );

        // Auto-generate handle if not provided
        $handle ??= StringHelper::toHandle($name);

        $productTitleTranslationMethod = $this->getTranslationMethodConstant($productTitleTranslationMethod);
        $variantTitleTranslationMethod = $this->getTranslationMethodConstant($variantTitleTranslationMethod);
        $slugTranslationMethod = $this->getTranslationMethodConstant($slugTranslationMethod);
        $defaultPlacement = $this->getDefaultPlacement($defaultPlacement);

        // Default variantTitleFormat when variant has a title field
        $variantTitleFormat ??= '{product.title}';

        // Create the product type
        $productType = new ProductType();
        $productType->name = $name;
        $productType->handle = $handle;
        $productType->hasProductTitleField = $hasProductTitleField;
        $productType->productTitleFormat = $productTitleFormat ?? '';
        $productType->productTitleTranslationMethod = $productTitleTranslationMethod;
        $productType->productTitleTranslationKeyFormat = $productTitleTranslationKeyFormat;
        $productType->hasVariantTitleField = $hasVariantTitleField;
        $productType->variantTitleFormat = $variantTitleFormat;
        $productType->variantTitleTranslationMethod = $variantTitleTranslationMethod;
        $productType->variantTitleTranslationKeyFormat = $variantTitleTranslationKeyFormat;
        $productType->showSlugField = $showSlugField;
        $productType->slugTranslationMethod = $slugTranslationMethod;
        $productType->slugTranslationKeyFormat = $slugTranslationKeyFormat;
        $productType->skuFormat = $skuFormat;
        $productType->descriptionFormat = $descriptionFormat;
        $productType->template = $template;
        $productType->hasDimensions = $hasDimensions;
        $productType->maxVariants = $maxVariants;
        $productType->enableVersioning = $enableVersioning;
        $productType->isStructure = $isStructure;
        $productType->defaultPlacement = $defaultPlacement;

        // Set structure-specific properties
        if ($isStructure && $maxLevels !== null && $maxLevels > 0) {
            $productType->maxLevels = $maxLevels;
        }

        // Set field layouts if provided
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

        // Configure site settings
        $siteSettingsObjects = [];

        if ($siteSettings !== null) {
            foreach ($siteSettings as $siteData) {
                $siteId = $siteData['siteId'];
                throw_unless(is_int($siteId), 'siteId must be an integer');

                $site = Craft::$app->getSites()->getSiteById($siteId);
                throw_unless($site, "Site with ID {$siteId} not found");

                $siteSettingsObjects[$siteId] = new ProductTypeSite([
                    'siteId' => $siteId,
                    'enabledByDefault' => $siteData['enabledByDefault'] ?? true,
                    'hasUrls' => $siteData['hasUrls'] ?? false,
                    'uriFormat' => $siteData['uriFormat'] ?? null,
                    'template' => $siteData['template'] ?? null,
                ]);
            }
        } else {
            // Default: enable for all sites
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                $siteSettingsObjects[$site->id] = new ProductTypeSite([
                    'siteId' => $site->id,
                    'enabledByDefault' => true,
                    'hasUrls' => false,
                ]);
            }
        }

        $productType->setSiteSettings($siteSettingsObjects);

        // Save the product type
        throw_unless(
            $commerce->getProductTypes()->saveProductType($productType),
            ModelSaveException::class,
            $productType,
        );

        return [
            '_notes' => 'The product type was successfully created. You can further configure it in the Craft control panel.',
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
