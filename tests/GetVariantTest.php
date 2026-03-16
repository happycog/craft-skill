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
    $variant->basePrice = 49.99;
    $variant->isDefault = true;
    $variant->weight = 2.5;
    $variant->height = 10.0;
    $variant->length = 20.0;
    $variant->width = 15.0;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    // Re-fetch product from DB to get saved variant with ID
    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();
    expect($savedVariant)->not->toBeNull('Variant should exist after save');

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
    $variant->basePrice = 19.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    // Re-fetch product from DB to get saved variant with ID
    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();
    expect($savedVariant)->not->toBeNull('Variant should exist after save');

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect($response['productId'])->toBe($product->id);
    expect($response['productTitle'])->toBe('Parent Product');
    expect($response['url'])->toBeString();
});

it('returns correct dimension values', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Dimensions Test Product';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-DIM-001';
    $variant->basePrice = 30.00;
    $variant->isDefault = true;
    $variant->weight = 5.25;
    $variant->height = 15.0;
    $variant->length = 30.0;
    $variant->width = 20.0;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect((float) $response['weight'])->toBe(5.25);
    expect((float) $response['height'])->toBe(15.0);
    expect((float) $response['length'])->toBe(30.0);
    expect((float) $response['width'])->toBe(20.0);
});

it('returns freeShipping and inventoryTracked flags', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Flags Test Product';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-FLAGS-001';
    $variant->basePrice = 5.00;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect($response['freeShipping'])->toBeBool();
    expect($response['inventoryTracked'])->toBeBool();
});

it('returns customFields as array', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $product = new \craft\commerce\elements\Product();
    $product->typeId = $productTypes[0]->id;
    $product->title = 'Variant Custom Fields Test';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-VCF-001';
    $variant->basePrice = 12.00;
    $variant->isDefault = true;
    $product->setVariants([$variant]);
    $product->setDirtyAttributes(['variants']);

    Craft::$app->getElements()->saveElement($product);

    $freshProduct = Craft::$app->getElements()->getElementById($product->id, \craft\commerce\elements\Product::class);
    $savedVariant = $freshProduct->getVariants()->first();

    $response = $this->tool->__invoke(variantId: $savedVariant->id);

    expect($response['customFields'])->toBeArray();
});
