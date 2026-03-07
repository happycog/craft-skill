<?php

namespace happycog\craftmcp\tools;

use craft\commerce\elements\Product;
use craft\commerce\models\ProductType;
use craft\commerce\Plugin as Commerce;

class DeleteProductType
{
    /**
     * Delete a Commerce product type.
     *
     * **WARNING**: Deleting a product type that has existing products will cause data loss.
     * The tool will provide usage statistics and require confirmation for product types with
     * existing content.
     *
     * You _must_ get the user's approval to use the force parameter to delete product types
     * that have existing products. This action cannot be undone.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The ID of the product type to delete */
        int $productTypeId,

        /** Force deletion even if products exist (default: false) */
        bool $force = false,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $productType = $commerce->getProductTypes()->getProductTypeById($productTypeId);

        throw_unless(
            $productType instanceof ProductType,
            \InvalidArgumentException::class,
            "Product type with ID {$productTypeId} not found",
        );

        // Analyze impact before deletion
        $impact = $this->analyzeImpact($productType);

        // Check if force is required
        if ($impact['hasContent'] && !$force) {
            assert(is_int($impact['productCount']) || is_string($impact['productCount']));
            $productCount = (string) $impact['productCount'];

            throw new \RuntimeException(
                "Product type '{$productType->name}' contains data and cannot be deleted without force=true.\n\n" .
                "Impact Assessment:\n" .
                "- Products: {$productCount}\n\n" .
                "Set force=true to proceed with deletion. This action cannot be undone.",
            );
        }

        // Store product type info for response
        $productTypeInfo = [
            '_notes' => 'The product type was successfully deleted.',
            'id' => $productType->id,
            'name' => $productType->name,
            'handle' => $productType->handle,
            'impact' => $impact,
        ];

        // Delete the product type
        throw_unless(
            $commerce->getProductTypes()->deleteProductTypeById($productTypeId),
            "Failed to delete product type with ID {$productTypeId}.",
        );

        return $productTypeInfo;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeImpact(ProductType $productType): array
    {
        $productCount = Product::find()
            ->typeId($productType->id)
            ->status(null)
            ->count();

        return [
            'hasContent' => $productCount > 0,
            'productCount' => $productCount,
        ];
    }
}
