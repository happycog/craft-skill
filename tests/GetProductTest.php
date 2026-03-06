<?php

use happycog\craftmcp\tools\GetProduct;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Product::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetProduct::class);
});

it('throws exception for non-existent product', function () {
    expect(fn () => $this->tool->__invoke(productId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Product with ID 99999 not found');
});

it('retrieves product details with expected structure', function () {
    // Create a product programmatically
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $productType = $productTypes[0];

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productType->id;
    $product->title = 'Test Product for GetProduct';
    $product->slug = 'test-product-get';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-GET-001';
    $variant->price = 19.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    $success = Craft::$app->getElements()->saveElement($product);
    expect($success)->toBeTrue();

    $response = $this->tool->__invoke(productId: $product->id);

    expect($response)->toHaveKeys([
        '_notes',
        'productId',
        'title',
        'slug',
        'status',
        'typeId',
        'typeName',
        'typeHandle',
        'postDate',
        'expiryDate',
        'defaultSku',
        'defaultPrice',
        'url',
        'variants',
        'customFields',
    ]);
    expect($response['_notes'])->toBe('Retrieved product details with variants.');
    expect($response['productId'])->toBe($product->id);
    expect($response['title'])->toBe('Test Product for GetProduct');
    expect($response['typeName'])->toBe($productType->name);
});

it('includes variant details in response', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Product With Variants';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-VAR-001';
    $variant->price = 29.99;
    $variant->isDefault = true;
    $variant->stock = 50;
    $variant->weight = 1.5;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke(productId: $product->id);

    expect($response['variants'])->toBeArray();
    expect($response['variants'])->not->toBeEmpty();

    $firstVariant = $response['variants'][0];
    expect($firstVariant)->toHaveKeys([
        'id',
        'title',
        'sku',
        'price',
        'isDefault',
        'stock',
        'minQty',
        'maxQty',
        'weight',
        'height',
        'length',
        'width',
        'freeShipping',
        'inventoryTracked',
        'sortOrder',
    ]);
    expect($firstVariant['sku'])->toBe('TEST-VAR-001');
    expect($firstVariant['price'])->toBe(29.99);
    expect($firstVariant['isDefault'])->toBeTrue();
});
