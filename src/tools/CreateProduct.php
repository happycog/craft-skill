<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ElementHelper;

class CreateProduct
{
    /**
     * Create a new Commerce product.
     *
     * Creates a product with the given product type, title, and optional attributes. A default
     * variant is created automatically with the provided SKU and price. Use the CreateVariant tool
     * to add additional variants after creation.
     *
     * After creating the product, link the user to the product in the Craft control panel so
     * they can review it.
     *
     * @param array<string, mixed> $fields Custom field data keyed by field handle.
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The product type ID. Use GetProductTypes to discover available types. */
        int $typeId,

        /** Product title. */
        string $title,

        /** SKU for the default variant. */
        string $sku,

        /** Price for the default variant. */
        float $price,

        /** Product slug. Auto-generated from title if not provided. */
        ?string $slug = null,

        /** Post date in ISO 8601 format. Defaults to now. */
        ?string $postDate = null,

        /** Expiry date in ISO 8601 format. Null means no expiry. */
        ?string $expiryDate = null,

        /** Whether the product is enabled. Default: true. */
        bool $enabled = true,

        /** Custom field data keyed by field handle. */
        array $fields = [],
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $productType = $commerce->getProductTypes()->getProductTypeById($typeId);
        throw_unless($productType, \InvalidArgumentException::class, "Product type with ID {$typeId} not found");

        $product = new Product();
        $product->typeId = $typeId;
        $product->title = $title;
        $product->enabled = $enabled;

        if ($slug !== null) {
            $product->slug = $slug;
        }
        if ($postDate !== null) {
            $product->postDate = new \DateTime($postDate);
        }
        if ($expiryDate !== null) {
            $product->expiryDate = new \DateTime($expiryDate);
        }
        if (!empty($fields)) {
            $product->setFieldValues($fields);
        }

        // Create the default variant
        $variant = new Variant();
        $variant->sku = $sku;
        $variant->basePrice = $price;
        $variant->isDefault = true;

        $product->setVariants([$variant]);
        $product->setDirtyAttributes(['variants']);

        throw_unless(
            Craft::$app->getElements()->saveElement($product),
            "Failed to save product: " . implode(', ', $product->getFirstErrors()),
        );

        return [
            '_notes' => 'The product was successfully created.',
            'productId' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'status' => $product->getStatus(),
            'typeId' => $product->typeId,
            'typeName' => $productType->name,
            'defaultSku' => $product->defaultSku,
            'defaultPrice' => $product->defaultPrice,
            'url' => ElementHelper::elementEditorUrl($product),
        ];
    }
}
