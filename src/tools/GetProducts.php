<?php

namespace happycog\craftmcp\tools;

use craft\commerce\elements\Product;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ElementHelper;
use Illuminate\Support\Collection;

class GetProducts
{
    /**
     * Search and list Commerce products with optional filtering.
     *
     * Returns a list of products matching the given criteria. Use this to discover products
     * before retrieving full details with GetProduct. Supports filtering by product type,
     * status, and search query.
     *
     * @param array<int>|null $typeIds
     * @return array<string, mixed>
     */
    public function __invoke(
        ?string $query = null,
        int $limit = 10,

        /** Product status filter. Options: live, pending, expired, disabled. Default: live. */
        string $status = Product::STATUS_LIVE,

        /** Optional array of product type IDs to filter results. */
        ?array $typeIds = null,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        // Validate product type IDs if provided
        if ($typeIds !== null) {
            foreach ($typeIds as $typeId) {
                $type = $commerce->getProductTypes()->getProductTypeById($typeId);
                throw_unless($type, "Product type with ID {$typeId} not found");
            }
        }

        $queryBuilder = Product::find()->limit($limit)->status($status);

        if ($typeIds !== null) {
            $queryBuilder->typeId($typeIds);
        }

        if ($query !== null) {
            $queryBuilder->search($query);
        }

        $result = $queryBuilder->all();

        // Generate descriptive notes
        $notes = [];
        if ($query !== null) {
            $notes[] = "search query \"{$query}\"";
        }
        if ($typeIds !== null) {
            $typeNames = [];
            foreach ($typeIds as $typeId) {
                $type = $commerce->getProductTypes()->getProductTypeById($typeId);
                if ($type !== null) {
                    $typeNames[] = $type->name;
                }
            }
            $notes[] = 'product type(s): ' . implode(', ', $typeNames);
        }

        $notesText = empty($notes)
            ? 'The following products were found.'
            : 'The following products were found matching ' . implode(' and ', $notes) . '.';

        return [
            '_notes' => $notesText,
            'results' => Collection::make($result)->map(function (Product $product) {
                return [
                    'productId' => (int) $product->id,
                    'title' => (string) $product->title,
                    'slug' => $product->slug,
                    'status' => $product->getStatus(),
                    'typeId' => $product->typeId,
                    'defaultSku' => $product->getDefaultSku(),
                    'defaultPrice' => $product->getDefaultPrice(),
                    'url' => ElementHelper::elementEditorUrl($product),
                ];
            }),
        ];
    }
}
