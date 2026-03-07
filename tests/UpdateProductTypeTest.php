<?php

use happycog\craftmcp\tools\CreateProductType;
use happycog\craftmcp\tools\UpdateProductType;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->createTool = Craft::$container->get(CreateProductType::class);
    $this->tool = Craft::$container->get(UpdateProductType::class);

    // Create a product type for testing updates
    $created = $this->createTool->__invoke(
        name: 'Update Test Type',
        handle: 'updateTestType' . random_int(1000, 9999),
    );
    $this->productTypeId = $created['id'];
});

it('updates product type name', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        name: 'Updated Name',
    );

    expect($response['name'])->toBe('Updated Name');
    expect($response['_notes'])->toBe('The product type was successfully updated.');
});

it('updates product type handle', function () {
    $newHandle = 'updatedHandle' . random_int(1000, 9999);
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        handle: $newHandle,
    );

    expect($response['handle'])->toBe($newHandle);
});

it('updates hasDimensions', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        hasDimensions: true,
    );

    expect($response['hasDimensions'])->toBeTrue();
});

it('updates max variants', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        maxVariants: 10,
    );

    expect($response['maxVariants'])->toBe(10);
});

it('updates versioning setting', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        enableVersioning: true,
    );

    expect($response['enableVersioning'])->toBeTrue();
});

it('updates SKU format', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        skuFormat: '{product.slug}-{sku}',
    );

    expect($response['skuFormat'])->toBe('{product.slug}-{sku}');
});

it('updates product title field settings', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        hasProductTitleField: false,
        productTitleFormat: '{dateCreated|date}',
    );

    expect($response['hasProductTitleField'])->toBeFalse();
    expect($response['productTitleFormat'])->toBe('{dateCreated|date}');
});

it('updates variant title field settings', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        hasVariantTitleField: false,
        variantTitleFormat: '{product.title} - variant',
    );

    expect($response['hasVariantTitleField'])->toBeFalse();
    expect($response['variantTitleFormat'])->toBe('{product.title} - variant');
});

it('preserves unchanged settings when updating', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $before = $commerce->getProductTypes()->getProductTypeById($this->productTypeId);
    $originalHandle = $before->handle;

    $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        name: 'Only Name Changed',
    );

    $after = $commerce->getProductTypes()->getProductTypeById($this->productTypeId);
    expect($after->name)->toBe('Only Name Changed');
    expect($after->handle)->toBe($originalHandle);
});

it('handles empty update gracefully', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
    );

    expect($response['id'])->toBe($this->productTypeId);
    expect($response['_notes'])->toBe('The product type was successfully updated.');
});

it('returns proper response structure', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        name: 'Response Check',
    );

    expect($response)->toHaveKeys([
        '_notes',
        'id',
        'name',
        'handle',
        'fieldLayoutId',
        'variantFieldLayoutId',
        'hasProductTitleField',
        'productTitleFormat',
        'hasVariantTitleField',
        'variantTitleFormat',
        'skuFormat',
        'hasDimensions',
        'maxVariants',
        'enableVersioning',
        'editUrl',
        'editVariantUrl',
    ]);
});

it('throws exception for non-existent product type', function () {
    expect(fn () => $this->tool->__invoke(productTypeId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Product type with ID 99999 not found');
});

it('throws exception when disabling product title field without format', function () {
    expect(fn () => $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        hasProductTitleField: false,
    ))->toThrow(\InvalidArgumentException::class, 'Product title format is required');
});

it('throws exception when disabling variant title field without format', function () {
    expect(fn () => $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        hasVariantTitleField: false,
        variantTitleFormat: '',
    ))->toThrow(\InvalidArgumentException::class, 'Variant title format is required');
});

it('can update multiple settings at once', function () {
    $response = $this->tool->__invoke(
        productTypeId: $this->productTypeId,
        name: 'Multi Update',
        hasDimensions: true,
        maxVariants: 3,
        enableVersioning: true,
    );

    expect($response['name'])->toBe('Multi Update');
    expect($response['hasDimensions'])->toBeTrue();
    expect($response['maxVariants'])->toBe(3);
    expect($response['enableVersioning'])->toBeTrue();
});
