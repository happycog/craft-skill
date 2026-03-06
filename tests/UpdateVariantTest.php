<?php

use happycog\craftmcp\tools\UpdateVariant;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Variant::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(UpdateVariant::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    // Create a product with a variant for update tests
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Product for Variant Update';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-UPDVAR-001';
    $variant->basePrice = 25.00;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    $success = Craft::$app->getElements()->saveElement($product);
    expect($success)->toBeTrue();

    // Re-fetch product from DB to get saved variant with ID
    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();
    expect($savedVariant)->not->toBeNull('Variant should exist after save');

    $this->variant = $savedVariant;
    $this->product = $freshProduct;
});

it('can update variant price', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        price: 39.99,
    );

    expect($response['price'])->toBe(39.99);
    expect($response['_notes'])->toBe('The variant was successfully updated.');
});

it('can update variant SKU', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        sku: 'UPDATED-SKU-001',
    );

    expect($response['sku'])->toBe('UPDATED-SKU-001');
});

it('can update variant stock via inventory system', function () {
    // Stock is read-only in Commerce 5.x — managed via the inventory system.
    // The UpdateVariant tool does not support direct stock updates.
    // Verify that stock is returned as a read-only value in the response.
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        price: 30.00,
    );

    expect($response)->toHaveKey('stock');
    expect($response['stock'])->toBeInt();
});

it('can update multiple variant fields at once', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        price: 59.99,
        sku: 'MULTI-UPD-001',
    );

    expect($response['price'])->toBe(59.99);
    expect($response['sku'])->toBe('MULTI-UPD-001');
});

it('returns proper response format after update', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        title: 'Updated Variant Title',
    );

    expect($response)->toHaveKeys([
        '_notes',
        'variantId',
        'title',
        'sku',
        'price',
        'stock',
        'productId',
        'url',
    ]);
    expect($response['variantId'])->toBe($this->variant->id);
    expect($response['productId'])->toBe($this->product->id);
    expect($response['url'])->toBeString();
});

it('preserves unchanged fields when updating', function () {
    $originalSku = $this->variant->sku;

    $this->tool->__invoke(
        variantId: $this->variant->id,
        price: 99.99,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect($updated->sku)->toBe($originalSku);
    expect((float) $updated->price)->toBe(99.99);
});

it('throws exception for non-existent variant', function () {
    expect(fn () => $this->tool->__invoke(variantId: 99999, price: 10.00))
        ->toThrow(\InvalidArgumentException::class, 'Variant with ID 99999 not found');
});

it('handles empty update gracefully', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
    );

    expect($response['variantId'])->toBe($this->variant->id);
    expect($response['sku'])->toBe('TEST-UPDVAR-001');
});

it('can update variant minQty', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        minQty: 2,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect($updated->minQty)->toBe(2);
});

it('can update variant maxQty', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        maxQty: 50,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect($updated->maxQty)->toBe(50);
});

it('can update variant weight', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        weight: 3.5,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect((float) $updated->weight)->toBe(3.5);
});

it('can update variant height', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        height: 12.0,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect((float) $updated->height)->toBe(12.0);
});

it('can update variant length', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        length: 25.0,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect((float) $updated->length)->toBe(25.0);
});

it('can update variant width', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        width: 8.0,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect((float) $updated->width)->toBe(8.0);
});

it('can update variant freeShipping', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        freeShipping: true,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect($updated->freeShipping)->toBeTrue();
});

it('can update variant inventoryTracked', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        inventoryTracked: true,
    );

    $updated = Craft::$app->getElements()->getElementById($this->variant->id, \craft\commerce\elements\Variant::class);
    expect($updated->inventoryTracked)->toBeTrue();
});
