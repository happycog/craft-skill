<?php

use happycog\craftmcp\tools\CreateProduct;
use happycog\craftmcp\tools\CreateProductType;
use happycog\craftmcp\tools\DeleteProductType;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->createTool = Craft::$container->get(CreateProductType::class);
    $this->createProductTool = Craft::$container->get(CreateProduct::class);
    $this->tool = Craft::$container->get(DeleteProductType::class);
});

it('deletes an empty product type', function () {
    $created = $this->createTool->__invoke(
        name: 'Delete Test Type',
        handle: 'deleteTestType' . random_int(1000, 9999),
    );

    $response = $this->tool->__invoke(
        productTypeId: $created['id'],
    );

    expect($response['_notes'])->toBe('The product type was successfully deleted.');
    expect($response['id'])->toBe($created['id']);
    expect($response['name'])->toBe('Delete Test Type');
    expect($response['impact'])->toHaveKey('hasContent');
    expect($response['impact']['hasContent'])->toBeFalse();
    expect($response['impact']['productCount'])->toBe(0);
});

it('returns proper response structure after deletion', function () {
    $created = $this->createTool->__invoke(
        name: 'Structure Delete Type',
        handle: 'structDeleteType' . random_int(1000, 9999),
    );

    $response = $this->tool->__invoke(
        productTypeId: $created['id'],
    );

    expect($response)->toHaveKeys([
        '_notes',
        'id',
        'name',
        'handle',
        'impact',
    ]);
    expect($response['impact'])->toHaveKeys([
        'hasContent',
        'productCount',
    ]);
});

it('throws exception for non-existent product type', function () {
    expect(fn () => $this->tool->__invoke(productTypeId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Product type with ID 99999 not found');
});

it('deletes product type with force when products exist', function () {
    // Create a product type
    $created = $this->createTool->__invoke(
        name: 'Force Delete Type',
        handle: 'forceDeleteType' . random_int(1000, 9999),
    );

    // Create a product in this type
    $this->createProductTool->__invoke(
        typeId: $created['id'],
        title: 'Test Product for Deletion',
        sku: 'DELETE-TEST-SKU-' . random_int(1000, 9999),
        price: 10.00,
    );

    // Try to delete without force — should throw
    expect(fn () => $this->tool->__invoke(
        productTypeId: $created['id'],
    ))->toThrow(\RuntimeException::class, 'cannot be deleted without force=true');

    // Delete with force — should succeed
    $response = $this->tool->__invoke(
        productTypeId: $created['id'],
        force: true,
    );

    expect($response['_notes'])->toBe('The product type was successfully deleted.');
    expect($response['impact']['hasContent'])->toBeTrue();
    expect($response['impact']['productCount'])->toBeGreaterThan(0);
});

it('blocks deletion of product type with products when force is false', function () {
    // Create a product type
    $created = $this->createTool->__invoke(
        name: 'Block Delete Type',
        handle: 'blockDeleteType' . random_int(1000, 9999),
    );

    // Create a product in this type
    $this->createProductTool->__invoke(
        typeId: $created['id'],
        title: 'Block Delete Product',
        sku: 'BLOCK-DEL-SKU-' . random_int(1000, 9999),
        price: 5.00,
    );

    try {
        $this->tool->__invoke(productTypeId: $created['id']);
        $this->fail('Expected RuntimeException was not thrown');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toContain('contains data');
        expect($e->getMessage())->toContain('force=true');
        expect($e->getMessage())->toContain('Impact Assessment');
    }
});
