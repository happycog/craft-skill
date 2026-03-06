<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\helpers\ElementHelper;

class DeleteProduct
{
    /**
     * Delete a Commerce product.
     *
     * By default performs a soft delete where the product is marked as deleted but can be restored.
     * Set permanentlyDelete to true to permanently remove the product and all its variants.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $productId,

        /** Set to true to permanently delete the product. Default is false (soft delete). */
        bool $permanentlyDelete = false,
    ): array {
        $product = Craft::$app->getElements()->getElementById($productId, Product::class);

        throw_unless($product instanceof Product, \InvalidArgumentException::class, "Product with ID {$productId} not found");

        $productType = $product->getType();
        $productInfo = [
            '_notes' => 'The product was successfully deleted.',
            'productId' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'typeId' => $product->typeId,
            'typeName' => $productType->name,
            'deletedPermanently' => $permanentlyDelete,
        ];

        $elementsService = Craft::$app->getElements();
        throw_unless(
            $elementsService->deleteElement($product, $permanentlyDelete),
            "Failed to delete product with ID {$productId}.",
        );

        return $productInfo;
    }
}
