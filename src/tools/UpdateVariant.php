<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\ElementHelper;

class UpdateVariant
{
    private function getVariantStock(Variant $variant): int
    {
        return $variant->getStock();
    }

    /**
     * Update an existing Commerce product variant.
     *
     * Updates the variant's pricing, SKU, inventory, dimensions, and custom field values.
     * Use bracket notation for field data on the CLI:
     *   agent-craft variants/update 456 --price=29.99 --sku="WIDGET-LG"
     *
     * @param array<string, mixed> $fields Custom field data keyed by field handle.
     * @return array<string, mixed>
     */
    public function __invoke(
        int $variantId,

        /** Variant SKU. */
        ?string $sku = null,

        /** Variant price. */
        ?float $price = null,

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
        $variant = Craft::$app->getElements()->getElementById($variantId, Variant::class);

        throw_unless($variant instanceof Variant, \InvalidArgumentException::class, "Variant with ID {$variantId} not found");

        if ($sku !== null) {
            $variant->sku = $sku;
        }
        if ($price !== null) {
            $variant->basePrice = $price;
        }
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

        throw_unless(
            Craft::$app->getElements()->saveElement($variant),
            "Failed to save variant: " . implode(', ', $variant->getFirstErrors()),
        );

        // Re-fetch to get fresh values (price getter uses cached _price that may be stale)
        $variant = Craft::$app->getElements()->getElementById($variantId, Variant::class);
        throw_unless($variant instanceof Variant, \RuntimeException::class, "Failed to reload variant with ID {$variantId} after update");

        $product = $variant->getOwner();

        return [
            '_notes' => 'The variant was successfully updated.',
            'variantId' => $variant->id,
            'title' => $variant->title,
            'sku' => $variant->sku,
            'price' => (float) $variant->price,
            'stock' => $this->getVariantStock($variant),
            'productId' => $product instanceof Product ? $product->id : null,
            'url' => $product instanceof Product ? ElementHelper::elementEditorUrl($product) : null,
        ];
    }
}
