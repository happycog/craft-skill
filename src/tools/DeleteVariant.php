<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\ElementHelper;

class DeleteVariant
{
    /**
     * Delete a Commerce product variant.
     *
     * Removes a variant from its parent product. The default variant cannot be deleted — you
     * must first set another variant as default. By default performs a soft delete; set
     * permanentlyDelete to true for permanent removal.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $variantId,

        /** Set to true to permanently delete the variant. Default is false (soft delete). */
        bool $permanentlyDelete = false,
    ): array {
        $variant = Craft::$app->getElements()->getElementById($variantId, Variant::class);

        throw_unless($variant instanceof Variant, \InvalidArgumentException::class, "Variant with ID {$variantId} not found");

        $product = $variant->getOwner();
        $variantInfo = [
            '_notes' => 'The variant was successfully deleted.',
            'variantId' => $variant->id,
            'title' => $variant->title,
            'sku' => $variant->sku,
            'productId' => $product instanceof Product ? $product->id : null,
            'productTitle' => $product instanceof Product ? $product->title : null,
            'deletedPermanently' => $permanentlyDelete,
        ];

        throw_unless(
            Craft::$app->getElements()->deleteElement($variant, $permanentlyDelete),
            "Failed to delete variant with ID {$variantId}.",
        );

        return $variantInfo;
    }
}
