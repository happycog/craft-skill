<?php

use happycog\craftmcp\tools\CreateProductType;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(CreateProductType::class);
});

it('creates a product type with minimal required fields', function () {
    $response = $this->tool->__invoke(
        name: 'Test Product Type',
    );

    expect($response['_notes'])->toContain('successfully created');
    expect($response['id'])->toBeInt();
    expect($response['name'])->toBe('Test Product Type');
    expect($response['handle'])->toBe('testProductType');
    expect($response['hasProductTitleField'])->toBeTrue();
    expect($response['hasVariantTitleField'])->toBeTrue();
    expect($response['editUrl'])->toBeString();
    expect($response['editVariantUrl'])->toBeString();
});

it('creates a product type with custom handle', function () {
    $response = $this->tool->__invoke(
        name: 'Custom Handle Type',
        handle: 'myCustomHandle',
    );

    expect($response['handle'])->toBe('myCustomHandle');
});

it('creates a product type with dimensions enabled', function () {
    $response = $this->tool->__invoke(
        name: 'Physical Product Type',
        hasDimensions: true,
    );

    expect($response['hasDimensions'])->toBeTrue();
});

it('creates a product type with max variants', function () {
    $response = $this->tool->__invoke(
        name: 'Limited Variants Type',
        maxVariants: 5,
    );

    expect($response['maxVariants'])->toBe(5);
});

it('creates a product type with versioning enabled', function () {
    $response = $this->tool->__invoke(
        name: 'Versioned Product Type',
        enableVersioning: true,
    );

    expect($response['enableVersioning'])->toBeTrue();
});

it('creates a product type without product title field', function () {
    $response = $this->tool->__invoke(
        name: 'Auto Title Type',
        hasProductTitleField: false,
        productTitleFormat: '{dateCreated|date}',
    );

    expect($response['hasProductTitleField'])->toBeFalse();
    expect($response['productTitleFormat'])->toBe('{dateCreated|date}');
});

it('creates a product type without variant title field', function () {
    $response = $this->tool->__invoke(
        name: 'Auto Variant Title Type',
        hasVariantTitleField: false,
        variantTitleFormat: '{product.title} - {sku}',
    );

    expect($response['hasVariantTitleField'])->toBeFalse();
    expect($response['variantTitleFormat'])->toBe('{product.title} - {sku}');
});

it('creates a product type with SKU format', function () {
    $response = $this->tool->__invoke(
        name: 'Auto SKU Type',
        skuFormat: '{product.slug}',
    );

    expect($response['skuFormat'])->toBe('{product.slug}');
});

it('throws exception when product title format missing but title field disabled', function () {
    expect(fn () => $this->tool->__invoke(
        name: 'Bad Title Config',
        hasProductTitleField: false,
    ))->toThrow(\InvalidArgumentException::class, "hasProductTitleField");
});

it('throws exception when variant title format missing but title field disabled', function () {
    expect(fn () => $this->tool->__invoke(
        name: 'Bad Variant Title Config',
        hasVariantTitleField: false,
    ))->toThrow(\InvalidArgumentException::class, "hasVariantTitleField");
});

it('returns proper response structure', function () {
    $response = $this->tool->__invoke(
        name: 'Structure Check Type',
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

it('returns control panel edit URLs', function () {
    $response = $this->tool->__invoke(
        name: 'URL Check Type',
    );

    expect($response['editUrl'])->toContain('commerce/settings/producttypes/');
    expect($response['editVariantUrl'])->toContain('commerce/settings/producttypes/');
});

it('auto-generates handle from name', function () {
    $response = $this->tool->__invoke(
        name: 'My Cool Product Type',
    );

    expect($response['handle'])->toBe('myCoolProductType');
});
