<?php

use happycog\craftmcp\tools\UpdateProduct;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Product::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(UpdateProduct::class);

    // Create a reusable product for update tests
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Original Product Title';
    $product->slug = 'original-product-slug';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-UPD-001';
    $variant->price = 19.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    $success = Craft::$app->getElements()->saveElement($product);
    expect($success)->toBeTrue();

    $this->product = $product;
});

it('can update product title', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        title: 'Updated Product Title',
    );

    expect($response['title'])->toBe('Updated Product Title');
    expect($response['_notes'])->toBe('The product was successfully updated.');

    $updated = Craft::$app->getElements()->getElementById($this->product->id, \craft\commerce\elements\Product::class);
    expect($updated->title)->toBe('Updated Product Title');
});

it('can update product slug', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        slug: 'new-product-slug',
    );

    expect($response['slug'])->toBe('new-product-slug');
});

it('can disable a product', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        enabled: false,
    );

    expect($response['status'])->toBe('disabled');
});

it('returns proper response format after update', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
        title: 'Format Test',
    );

    expect($response)->toHaveKeys([
        '_notes',
        'productId',
        'title',
        'slug',
        'status',
        'url',
    ]);
    expect($response['productId'])->toBe($this->product->id);
    expect($response['url'])->toBeString();
});

it('preserves unchanged fields when updating', function () {
    $originalSlug = $this->product->slug;

    $this->tool->__invoke(
        productId: $this->product->id,
        title: 'Only Title Changed',
    );

    $updated = Craft::$app->getElements()->getElementById($this->product->id, \craft\commerce\elements\Product::class);
    expect($updated->title)->toBe('Only Title Changed');
    expect($updated->slug)->toBe($originalSlug);
});

it('throws exception for non-existent product', function () {
    expect(fn () => $this->tool->__invoke(productId: 99999, title: 'Test'))
        ->toThrow(\InvalidArgumentException::class, 'Product with ID 99999 not found');
});

it('handles empty update gracefully', function () {
    $response = $this->tool->__invoke(
        productId: $this->product->id,
    );

    expect($response['productId'])->toBe($this->product->id);
    expect($response['title'])->toBe('Original Product Title');
});
