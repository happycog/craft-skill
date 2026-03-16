<?php

use happycog\craftmcp\tools\CreateProduct;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Product::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(CreateProduct::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $this->productType = $productTypes[0];
});

it('creates a product with required fields', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'New Test Product',
        sku: 'CREATE-PROD-001',
        price: 29.99,
    );

    expect($response['_notes'])->toBe('The product was successfully created.');
    expect($response['productId'])->toBeInt();
    expect($response['title'])->toBe('New Test Product');
    expect($response['status'])->toBe('live');
    expect($response['typeId'])->toBe($this->productType->id);
    expect($response['typeName'])->toBe($this->productType->name);
    expect($response['defaultSku'])->toBe('CREATE-PROD-001');
    expect($response['defaultPrice'])->toBeNumeric();
    expect($response['url'])->toBeString();
});

it('creates a product with custom slug', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'Slug Test Product',
        sku: 'CREATE-PROD-SLUG',
        price: 10.00,
        slug: 'my-custom-slug',
    );

    expect($response['slug'])->toBe('my-custom-slug');
});

it('creates a product with postDate', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'PostDate Product',
        sku: 'CREATE-PROD-PD',
        price: 10.00,
        postDate: '2025-06-15T12:00:00+00:00',
    );

    expect($response['productId'])->toBeInt();

    // Verify the date was set (use midday to avoid timezone date-shift issues)
    $product = Craft::$app->getElements()->getElementById($response['productId'], \craft\commerce\elements\Product::class);
    expect($product->postDate->format('Y-m-d'))->toBe('2025-06-15');
});

it('creates a product with expiryDate', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'ExpiryDate Product',
        sku: 'CREATE-PROD-EXP',
        price: 10.00,
        expiryDate: '2030-12-31T23:59:59+00:00',
    );

    $product = Craft::$app->getElements()->getElementById($response['productId'], \craft\commerce\elements\Product::class);
    expect($product->expiryDate)->not->toBeNull();
    expect($product->expiryDate->format('Y-m-d'))->toBe('2030-12-31');
});

it('creates a disabled product', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'Disabled Product',
        sku: 'CREATE-PROD-DIS',
        price: 10.00,
        enabled: false,
    );

    expect($response['status'])->toBe('disabled');
});

it('creates a product with a default variant', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'Variant Check Product',
        sku: 'CREATE-PROD-VAR',
        price: 49.99,
    );

    // Verify the variant was created
    $product = Craft::$app->getElements()->getElementById($response['productId'], \craft\commerce\elements\Product::class);
    $variants = $product->getVariants()->all();

    expect($variants)->not->toBeEmpty();
    expect($variants[0]->sku)->toBe('CREATE-PROD-VAR');
    expect((float) $variants[0]->price)->toBe(49.99);
    expect($variants[0]->isDefault)->toBeTrue();
});

it('returns proper response structure', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'Structure Test',
        sku: 'CREATE-PROD-STR',
        price: 10.00,
    );

    expect($response)->toHaveKeys([
        '_notes',
        'productId',
        'title',
        'slug',
        'status',
        'typeId',
        'typeName',
        'defaultSku',
        'defaultPrice',
        'url',
    ]);
});

it('throws exception for invalid product type ID', function () {
    expect(fn () => $this->tool->__invoke(
        typeId: 99999,
        title: 'Bad Type',
        sku: 'CREATE-PROD-BAD',
        price: 10.00,
    ))->toThrow(\InvalidArgumentException::class, 'Product type with ID 99999 not found');
});

it('auto-generates slug from title', function () {
    $response = $this->tool->__invoke(
        typeId: $this->productType->id,
        title: 'Auto Slug Generation Test',
        sku: 'CREATE-PROD-AUTOSLUG',
        price: 10.00,
    );

    expect($response['slug'])->not->toBeNull();
    expect($response['slug'])->not->toBeEmpty();
});
