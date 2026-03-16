<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\ElementHelper;

class GetProduct
{
    private function getVariantStock(Variant $variant): int
    {
        return $variant->getStock();
    }

    /**
     * Get detailed information about a single Commerce product by ID.
     *
     * Returns the product's attributes, custom fields, and all associated variants
     * with their pricing, SKU, and inventory details.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $productId,
    ): array {
        $product = Craft::$app->getElements()->getElementById($productId, Product::class);

        throw_unless($product instanceof Product, \InvalidArgumentException::class, "Product with ID {$productId} not found");

        $variants = [];
        foreach ($product->getVariants() as $variant) {
            $variants[] = [
                'id' => $variant->id,
                'title' => $variant->title,
                'sku' => $variant->sku,
                'price' => (float) $variant->price,
                'isDefault' => $variant->isDefault,
                'stock' => $this->getVariantStock($variant),
                'minQty' => $variant->minQty,
                'maxQty' => $variant->maxQty,
                'weight' => $variant->weight,
                'height' => $variant->height,
                'length' => $variant->length,
                'width' => $variant->width,
                'freeShipping' => $variant->freeShipping,
                'inventoryTracked' => $variant->inventoryTracked,
                'sortOrder' => $variant->sortOrder,
            ];
        }

        $productType = $product->getType();

        return [
            '_notes' => 'Retrieved product details with variants.',
            'productId' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'status' => $product->getStatus(),
            'typeId' => $product->typeId,
            'typeName' => $productType->name,
            'typeHandle' => $productType->handle,
            'postDate' => $product->postDate?->format('c'),
            'expiryDate' => $product->expiryDate?->format('c'),
            'defaultSku' => $product->defaultSku,
            'defaultPrice' => $product->defaultPrice,
            'url' => ElementHelper::elementEditorUrl($product),
            'variants' => $variants,
            'customFields' => $product->getSerializedFieldValues(),
        ];
    }
}
