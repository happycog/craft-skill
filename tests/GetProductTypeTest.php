<?php

use happycog\craftmcp\tools\GetProductType;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetProductType::class);

    $commerce = \craft\commerce\Plugin::getInstance();
    $productTypes = $commerce->getProductTypes()->getAllProductTypes();

    if (empty($productTypes)) {
        $this->markTestSkipped('No product types configured in Commerce.');
    }

    $this->productType = $productTypes[0];
});

it('returns product type details with expected structure', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    expect($response)->toBeArray();
    expect($response)->toHaveKeys([
        '_notes',
        'id',
        'name',
        'handle',
        'fieldLayoutId',
        'variantFieldLayoutId',
        'hasDimensions',
        'hasProductTitleField',
        'productTitleFormat',
        'productTitleTranslationMethod',
        'hasVariantTitleField',
        'variantTitleFormat',
        'variantTitleTranslationMethod',
        'showSlugField',
        'slugTranslationMethod',
        'skuFormat',
        'descriptionFormat',
        'maxVariants',
        'enableVersioning',
        'isStructure',
        'propagationMethod',
        'siteSettings',
        'productFields',
        'variantFields',
        'editUrl',
        'editVariantUrl',
    ]);
    expect($response['_notes'])->toBe('Retrieved product type details with field layouts.');
});

it('returns correct types for product type fields', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    expect($response['id'])->toBeInt();
    expect($response['name'])->toBeString();
    expect($response['handle'])->toBeString();
    expect($response['hasDimensions'])->toBeBool();
    expect($response['hasProductTitleField'])->toBeBool();
    expect($response['hasVariantTitleField'])->toBeBool();
    expect($response['showSlugField'])->toBeBool();
    expect($response['enableVersioning'])->toBeBool();
    expect($response['isStructure'])->toBeBool();
    expect($response['propagationMethod'])->toBeString();
    expect($response['siteSettings'])->toBeArray();
    expect($response['productFields'])->toBeArray();
    expect($response['variantFields'])->toBeArray();
    expect($response['editUrl'])->toBeString();
    expect($response['editVariantUrl'])->toBeString();
});

it('returns the correct product type by ID', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    expect($response['id'])->toBe($this->productType->id);
    expect($response['name'])->toBe($this->productType->name);
    expect($response['handle'])->toBe($this->productType->handle);
});

it('returns site settings for the product type', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    expect($response['siteSettings'])->not->toBeEmpty();

    $siteSetting = $response['siteSettings'][0];
    expect($siteSetting)->toHaveKeys(['siteId', 'hasUrls', 'uriFormat', 'template', 'enabledByDefault']);
    expect($siteSetting['siteId'])->toBeInt();
    expect($siteSetting['hasUrls'])->toBeBool();
    expect($siteSetting['enabledByDefault'])->toBeBool();
});

it('returns control panel edit URLs', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    expect($response['editUrl'])->toContain('commerce/settings/producttypes/');
    expect($response['editVariantUrl'])->toContain('commerce/settings/producttypes/');
    expect($response['editVariantUrl'])->toContain('/variant');
});

it('returns structure fields as null when not a structure', function () {
    $response = $this->tool->__invoke(productTypeId: $this->productType->id);

    if (!$response['isStructure']) {
        expect($response['maxLevels'])->toBeNull();
        expect($response['defaultPlacement'])->toBeNull();
    }
});

it('throws exception for non-existent product type', function () {
    expect(fn () => $this->tool->__invoke(productTypeId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Product type with ID 99999 not found');
});
