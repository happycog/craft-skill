<?php

use happycog\craftmcp\tools\DeleteProduct;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Product::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(DeleteProduct::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $this->productType = $productTypes[0];
});

it('can soft delete a product (default behavior)', function () {
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $this->productType->id;
    $product->title = 'Product to Soft Delete';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-DEL-SOFT';
    $variant->price = 9.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke(productId: $product->id);

    expect($response['productId'])->toBe($product->id);
    expect($response['title'])->toBe('Product to Soft Delete');
    expect($response['deletedPermanently'])->toBeFalse();

    // Product should be soft deleted (trashed)
    $trashed = \craft\commerce\elements\Product::find()
        ->id($product->id)
        ->trashed()
        ->one();
    expect($trashed)->not->toBeNull();

    // Product should not be found in normal queries
    $live = \craft\commerce\elements\Product::find()->id($product->id)->one();
    expect($live)->toBeNull();
});

it('can permanently delete a product', function () {
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $this->productType->id;
    $product->title = 'Product to Permanently Delete';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-DEL-PERM';
    $variant->price = 9.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke(
        productId: $product->id,
        permanentlyDelete: true,
    );

    expect($response['productId'])->toBe($product->id);
    expect($response['deletedPermanently'])->toBeTrue();

    // Product should be completely removed
    $trashed = \craft\commerce\elements\Product::find()
        ->id($product->id)
        ->trashed()
        ->one();
    expect($trashed)->toBeNull();

    $live = \craft\commerce\elements\Product::find()->id($product->id)->one();
    expect($live)->toBeNull();
});

it('returns proper response format after deletion', function () {
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $this->productType->id;
    $product->title = 'Delete Format Test';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-DEL-FMT';
    $variant->price = 9.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke(productId: $product->id);

    expect($response)->toHaveKeys([
        '_notes',
        'productId',
        'title',
        'slug',
        'typeId',
        'typeName',
        'deletedPermanently',
    ]);
    expect($response['_notes'])->toBe('The product was successfully deleted.');
    expect($response['typeName'])->toBe($this->productType->name);
    expect($response['deletedPermanently'])->toBeBool();
});

it('throws exception when product not found', function () {
    expect(fn () => $this->tool->__invoke(productId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Product with ID 99999 not found');
});

it('includes product type information in response', function () {
    $product = new \craft\commerce\elements\Product();
    $product->typeId = $this->productType->id;
    $product->title = 'Type Info Test';
    $product->enabled = true;

    $variant = new \craft\commerce\elements\Variant();
    $variant->sku = 'TEST-DEL-TYPE';
    $variant->price = 9.99;
    $variant->isDefault = true;
    $product->setVariants([$variant]);

    Craft::$app->getElements()->saveElement($product);

    $response = $this->tool->__invoke(productId: $product->id);

    expect($response['typeId'])->toBe($this->productType->id);
    expect($response['typeName'])->toBe($this->productType->name);
});
