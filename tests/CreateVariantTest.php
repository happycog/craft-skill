<?php

use happycog\craftmcp\tools\CreateVariant;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Variant::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(CreateVariant::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    // Ensure product type allows multiple variants for these tests
    $productType = $productTypes[0];
    if ($productType->maxVariants <= 1) {
        $productType->maxVariants = 10;
        $commerce->getProductTypes()->saveProductType($productType);
    }

    // Create a product to add variants to
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productType->id;
    $product->title = 'Product for CreateVariant Tests';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'CRVAR-DEFAULT';
    $variant->basePrice = 10.00;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    $success = Craft::$app->getElements()->saveElement($product);
    expect($success)->toBeTrue();

    $this->product = $product;
});

it('creates a variant with required fields', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-NEW-001',
        price: 25.00,
    );

    expect($response['_notes'])->toBe('The variant was successfully created.');
    expect($response['variantId'])->toBeInt();
    expect($response['sku'])->toBe('CRVAR-NEW-001');
    expect($response['price'])->toBe(25.00);
    expect($response['productId'])->toBe($this->product->id);
    expect($response['productTitle'])->toBe('Product for CreateVariant Tests');
    expect($response['url'])->toBeString();
});

it('creates a variant with title', function () {
    // Enable variant title field on the product type so titles can be set
    $commerce = \craft\commerce\Plugin::getInstance();
    $productType = $this->product->getType();
    if (!$productType->hasVariantTitleField) {
        $productType->hasVariantTitleField = true;
        $commerce->getProductTypes()->saveProductType($productType);
    }

    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-TITLE',
        price: 30.00,
        title: 'Large Size',
    );

    expect($response['title'])->toBe('Large Size');
});

it('creates a variant with dimensions', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-DIM',
        price: 20.00,
        weight: 2.5,
        height: 10.0,
        length: 20.0,
        width: 15.0,
    );

    // Verify dimensions were saved
    $variant = Craft::$app->getElements()->getElementById($response['variantId'], \craft\commerce\elements\Variant::class);
    expect((float) $variant->weight)->toBe(2.5);
    expect((float) $variant->height)->toBe(10.0);
    expect((float) $variant->length)->toBe(20.0);
    expect((float) $variant->width)->toBe(15.0);
});

it('creates a variant with quantity limits', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-QTY',
        price: 20.00,
        minQty: 2,
        maxQty: 10,
    );

    $variant = Craft::$app->getElements()->getElementById($response['variantId'], \craft\commerce\elements\Variant::class);
    expect($variant->minQty)->toBe(2);
    expect($variant->maxQty)->toBe(10);
});

it('creates a variant with free shipping', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-FREESHIP',
        price: 100.00,
        freeShipping: true,
    );

    $variant = Craft::$app->getElements()->getElementById($response['variantId'], \craft\commerce\elements\Variant::class);
    expect($variant->freeShipping)->toBeTrue();
});

it('returns proper response structure', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-STRUCT',
        price: 15.00,
    );

    expect($response)->toHaveKeys([
        '_notes',
        'variantId',
        'title',
        'sku',
        'price',
        'stock',
        'productId',
        'productTitle',
        'url',
    ]);
});

it('throws exception for non-existent product', function () {
    expect(fn () => $this->tool->__invoke(
        productId: 99999,
        sku: 'CRVAR-BAD',
        price: 10.00,
    ))->toThrow(\InvalidArgumentException::class, 'Product with ID 99999 not found');
});

it('appends variant to existing variants', function () {
    // Get initial variant count
    $freshProduct = Craft::$app->getElements()->getElementById($this->product->id, \craft\commerce\elements\Product::class);
    $initialCount = count($freshProduct->getVariants()->all());

    $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-APPEND',
        price: 35.00,
    );

    // Re-fetch and verify count increased
    $updatedProduct = Craft::$app->getElements()->getElementById($this->product->id, \craft\commerce\elements\Product::class);
    $newCount = count($updatedProduct->getVariants()->all());

    expect($newCount)->toBe($initialCount + 1);
});

it('returns stock as integer', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        sku: 'CRVAR-STOCK',
        price: 10.00,
    );

    expect($response['stock'])->toBeInt();
});
