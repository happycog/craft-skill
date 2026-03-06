<?php

use happycog\craftmcp\tools\GetVariant;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Variant::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetVariant::class);
});

it('throws exception for non-existent variant', function () {
    expect(fn () => $this->tool->__invoke(variantId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Variant with ID 99999 not found');
});

it('retrieves variant details with expected structure', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Product for Variant Test';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-GETVAR-001';
    $variant->price = 49.99;
    $variant->isDefault = true;
    $variant->stock = 100;
    $variant->weight = 2.5;
    $variant->height = 10.0;
    $variant->length = 20.0;
    $variant->width = 15.0;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    // Get the saved variant ID
    $savedVariants = $product->getVariants();
    $savedVariant = $savedVariants[0] ?? $savedVariants->first();

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect($response)->toHaveKeys([
        '_notes',
        'variantId',
        'title',
        'sku',
        'price',
        'isDefault',
        'sortOrder',
        'stock',
        'minQty',
        'maxQty',
        'weight',
        'height',
        'length',
        'width',
        'freeShipping',
        'inventoryTracked',
        'productId',
        'productTitle',
        'url',
        'customFields',
    ]);
    expect($response['_notes'])->toBe('Retrieved variant details.');
    expect($response['sku'])->toBe('TEST-GETVAR-001');
    expect($response['price'])->toBe(49.99);
    expect($response['isDefault'])->toBeTrue();
});

it('includes parent product information', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Parent Product';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-GETVAR-PARENT';
    $variant->price = 19.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $savedVariants = $product->getVariants();
    $savedVariant = $savedVariants[0] ?? $savedVariants->first();

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect($response['productId'])->toBe($product->id);
    expect($response['productTitle'])->toBe('Parent Product');
    expect($response['url'])->toBeString();
});
