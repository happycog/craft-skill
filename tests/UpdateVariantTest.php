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
    $variant->price = 25.00;
    $variant->isDefault = true;
    $variant->stock = 50;
    $product->setVariants([$variant]);

    $success = Craft::$app->getElements()->saveElement($product);
    expect($success)->toBeTrue();

    $savedVariants = $product->getVariants();
    $this->variant = $savedVariants[0] ?? $savedVariants->first();
    $this->product = $product;
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

it('can update variant stock', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        stock: 200,
    );

    expect($response['stock'])->toBe(200);
});

it('can update multiple variant fields at once', function () {
    $response = $this->tool->__invoke(
        variantId: $this->variant->id,
        price: 59.99,
        sku: 'MULTI-UPD-001',
        stock: 75,
    );

    expect($response['price'])->toBe(59.99);
    expect($response['sku'])->toBe('MULTI-UPD-001');
    expect($response['stock'])->toBe(75);
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
