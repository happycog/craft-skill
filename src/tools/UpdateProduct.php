<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Product;
use craft\helpers\ElementHelper;

class UpdateProduct
{
    /**
     * Update an existing Commerce product.
     *
     * Updates the product's attributes and custom field values. Use bracket notation for
     * field data on the CLI:
     *   agent-craft products/update 123 --title="New Title" --fields[bodyContent]="Updated"
     *
     * After updating, link the user to the product in the Craft control panel so they can
     * review changes.
     *
     * @param array<string, mixed> $fields Custom field data keyed by field handle.
     * @return array<string, mixed>
     */
    public function __invoke(
        int $productId,

        /** Product title. */
        ?string $title = null,

        /** Product slug. */
        ?string $slug = null,

        /** Post date in ISO 8601 format (e.g. 2025-01-01T00:00:00+00:00). */
        ?string $postDate = null,

        /** Expiry date in ISO 8601 format, or null to remove. */
        ?string $expiryDate = null,

        /** Whether the product is enabled. */
        ?bool $enabled = null,

        /** Custom field data keyed by field handle. */
        array $fields = [],
    ): array {
        $product = Craft::$app->getElements()->getElementById($productId, Product::class);

        throw_unless($product instanceof Product, \InvalidArgumentException::class, "Product with ID {$productId} not found");

        if ($title !== null) {
            $product->title = $title;
        }
        if ($slug !== null) {
            $product->slug = $slug;
        }
        if ($postDate !== null) {
            $product->postDate = new \DateTime($postDate);
        }
        if ($expiryDate !== null) {
            $product->expiryDate = new \DateTime($expiryDate);
        }
        if ($enabled !== null) {
            $product->enabled = $enabled;
        }
        if (!empty($fields)) {
            $product->setFieldValues($fields);
        }

        throw_unless(
            Craft::$app->getElements()->saveElement($product),
            "Failed to save product: " . implode(', ', $product->getFirstErrors()),
        );

        return [
            '_notes' => 'The product was successfully updated.',
            'productId' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'status' => $product->getStatus(),
            'url' => ElementHelper::elementEditorUrl($product),
        ];
    }
}
