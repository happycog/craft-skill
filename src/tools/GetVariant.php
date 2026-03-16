<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\ElementHelper;

class GetVariant
{
    private function getVariantStock(Variant $variant): int
    {
        return $variant->getStock();
    }

    /**
     * Get detailed information about a single Commerce product variant by ID.
     *
     * Returns the variant's pricing, inventory, dimensions, and custom field data,
     * along with information about its parent product.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $variantId,
    ): array {
        $variant = Craft::$app->getElements()->getElementById($variantId, Variant::class);

        throw_unless($variant instanceof Variant, \InvalidArgumentException::class, "Variant with ID {$variantId} not found");

        $product = $variant->getOwner();

        return [
            '_notes' => 'Retrieved variant details.',
            'variantId' => $variant->id,
            'title' => $variant->title,
            'sku' => $variant->sku,
            'price' => (float) $variant->price,
            'isDefault' => $variant->isDefault,
            'sortOrder' => $variant->sortOrder,
            'stock' => $this->getVariantStock($variant),
            'minQty' => $variant->minQty,
            'maxQty' => $variant->maxQty,
            'weight' => $variant->weight,
            'height' => $variant->height,
            'length' => $variant->length,
            'width' => $variant->width,
            'freeShipping' => $variant->freeShipping,
            'inventoryTracked' => $variant->inventoryTracked,
            'productId' => $product instanceof Product ? $product->id : null,
            'productTitle' => $product instanceof Product ? $product->title : null,
            'url' => $product instanceof Product ? ElementHelper::elementEditorUrl($product) : null,
            'customFields' => $variant->getSerializedFieldValues(),
        ];
    }
}
