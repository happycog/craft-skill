<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\ElementHelper;

class CreateVariant
{
    /**
     * Create a new variant on an existing Commerce product.
     *
     * Adds a new variant to the specified product with the given SKU, price, and optional
     * attributes. The product type must allow multiple variants (maxVariants > 1) for this
     * to work — single-variant product types already have a default variant.
     *
     * After creating the variant, link the user to the parent product in the Craft control
     * panel so they can review it.
     *
     * @param array<string, mixed> $fields Custom field data keyed by field handle.
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The parent product ID. */
        int $productId,

        /** Variant SKU. Must be unique. */
        string $sku,

        /** Variant price. */
        float $price,

        /** Variant title. */
        ?string $title = null,

        /** Minimum purchase quantity. */
        ?int $minQty = null,

        /** Maximum purchase quantity. */
        ?int $maxQty = null,

        /** Variant weight. */
        ?float $weight = null,

        /** Variant height. */
        ?float $height = null,

        /** Variant length. */
        ?float $length = null,

        /** Variant width. */
        ?float $width = null,

        /** Whether the variant qualifies for free shipping. */
        ?bool $freeShipping = null,

        /** Whether inventory is tracked for this variant. */
        ?bool $inventoryTracked = null,

        /** Custom field data keyed by field handle. */
        array $fields = [],
    ): array {
        $product = Craft::$app->getElements()->getElementById($productId, Product::class);

        throw_unless($product instanceof Product, \InvalidArgumentException::class, "Product with ID {$productId} not found");

        $variant = new Variant();
        $variant->sku = $sku;
        $variant->basePrice = $price;

        if ($title !== null) {
            $variant->title = $title;
        }
        if ($minQty !== null) {
            $variant->minQty = $minQty;
        }
        if ($maxQty !== null) {
            $variant->maxQty = $maxQty;
        }
        if ($weight !== null) {
            $variant->weight = $weight;
        }
        if ($height !== null) {
            $variant->height = $height;
        }
        if ($length !== null) {
            $variant->length = $length;
        }
        if ($width !== null) {
            $variant->width = $width;
        }
        if ($freeShipping !== null) {
            $variant->freeShipping = $freeShipping;
        }
        if ($inventoryTracked !== null) {
            $variant->inventoryTracked = $inventoryTracked;
        }
        if (!empty($fields)) {
            $variant->setFieldValues($fields);
        }

        // Append the new variant to existing variants
        $existingVariants = $product->getVariants()->all();
        $existingVariants[] = $variant;
        $product->setVariants($existingVariants);
        $product->setDirtyAttributes(['variants']);

        throw_unless(
            Craft::$app->getElements()->saveElement($product),
            "Failed to save product with new variant: " . implode(', ', $product->getFirstErrors()),
        );

        // Re-fetch to get the saved variant with its ID
        $freshProduct = Craft::$app->getElements()->getElementById($productId, Product::class);
        throw_unless($freshProduct instanceof Product, \RuntimeException::class, "Failed to reload product with ID {$productId} after creating variant");

        $savedVariants = $freshProduct->getVariants()->all();
        $newVariant = end($savedVariants);

        throw_unless($newVariant instanceof Variant, \RuntimeException::class, 'Failed to determine the saved variant after creating it');

        return [
            '_notes' => 'The variant was successfully created.',
            'variantId' => $newVariant->id,
            'title' => $newVariant->title,
            'sku' => $newVariant->sku,
            'price' => (float) $newVariant->price,
            'stock' => $newVariant->getStock(),
            'productId' => $freshProduct->id,
            'productTitle' => $freshProduct->title,
            'url' => ElementHelper::elementEditorUrl($freshProduct),
        ];
    }
}
