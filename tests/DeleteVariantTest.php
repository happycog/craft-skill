<?php

use happycog\craftmcp\tools\DeleteVariant;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Variant::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(DeleteVariant::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $this->productType = $productTypes[0];
});

/**
 * Helper to create a product with a variant and return both.
 *
 * @return array{product: \craft\commerce\elements\Product, variant: \craft\commerce\elements\Variant}
 */
function createProductWithVariant(string $sku = 'DEL-VAR-001', float $price = 9.99): array
{
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Product for Delete Variant';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = $sku;
    $variant->basePrice = $price;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();

    return ['product' => $freshProduct, 'variant' => $savedVariant];
}

it('can soft delete a variant (default behavior)', function () {
    $fixtures = createProductWithVariant('DEL-VAR-SOFT');
    $variant = $fixtures['variant'];

    $response = $this->tool->__invoke(variantId: $variant->id);

    expect($response['variantId'])->toBe($variant->id);
    expect($response['sku'])->toBe('DEL-VAR-SOFT');
    expect($response['deletedPermanently'])->toBeFalse();
    expect($response['_notes'])->toBe('The variant was successfully deleted.');

    // Variant should be soft deleted (trashed)
    $trashed = \craft\commerce\elements\Variant::find()
        ->id($variant->id)
        ->trashed()
        ->one();
    expect($trashed)->not->toBeNull();

    // Variant should not be found in normal queries
    $live = \craft\commerce\elements\Variant::find()->id($variant->id)->one();
    expect($live)->toBeNull();
});

it('can permanently delete a variant', function () {
    $fixtures = createProductWithVariant('DEL-VAR-PERM');
    $variant = $fixtures['variant'];

    $response = $this->tool->__invoke(
        variantId: $variant->id,
        permanentlyDelete: true,
    );

    expect($response['deletedPermanently'])->toBeTrue();

    // Variant should be completely gone
    $trashed = \craft\commerce\elements\Variant::find()
        ->id($variant->id)
        ->trashed()
        ->one();
    expect($trashed)->toBeNull();

    $live = \craft\commerce\elements\Variant::find()->id($variant->id)->one();
    expect($live)->toBeNull();
});

it('returns proper response format after deletion', function () {
    $fixtures = createProductWithVariant('DEL-VAR-FMT');

    $response = $this->tool->__invoke(variantId: $fixtures['variant']->id);

    expect($response)->toHaveKeys([
        '_notes',
        'variantId',
        'title',
        'sku',
        'productId',
        'productTitle',
        'deletedPermanently',
    ]);
    expect($response['productId'])->toBe($fixtures['product']->id);
    expect($response['productTitle'])->toBe('Product for Delete Variant');
});

it('throws exception when variant not found', function () {
    expect(fn () => $this->tool->__invoke(variantId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Variant with ID 99999 not found');
});

it('includes parent product information in response', function () {
    $fixtures = createProductWithVariant('DEL-VAR-PARENT');

    $response = $this->tool->__invoke(variantId: $fixtures['variant']->id);

    expect($response['productId'])->toBe($fixtures['product']->id);
    expect($response['productTitle'])->toBe($fixtures['product']->title);
});
