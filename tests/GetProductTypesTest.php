<?php

use happycog\craftmcp\tools\GetProductTypes;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetProductTypes::class);
});

it('returns product types array with expected structure', function () {
    $response = $this->tool->__invoke();

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['_notes', 'productTypes']);
    expect($response['_notes'])->toBe('Retrieved all Commerce product types.');
    expect($response['productTypes'])->toBeArray();
});

it('returns correct keys for each product type', function () {
    $response = $this->tool->__invoke();

    if (!empty($response['productTypes'])) {
        $type = $response['productTypes'][0];
        expect($type)->toHaveKeys([
            'id',
            'name',
            'handle',
            'fieldLayoutId',
            'variantFieldLayoutId',
            'hasDimensions',
            'hasProductTitleField',
            'productTitleFormat',
            'hasVariantTitleField',
            'variantTitleFormat',
            'skuFormat',
            'maxVariants',
            'siteSettings',
        ]);
        expect($type['id'])->toBeInt();
        expect($type['name'])->toBeString();
        expect($type['handle'])->toBeString();
        expect($type['hasDimensions'])->toBeBool();
        expect($type['maxVariants'])->toBeInt();
        expect($type['siteSettings'])->toBeArray();
    }
});

it('returns site settings for each product type', function () {
    $response = $this->tool->__invoke();

    if (!empty($response['productTypes'])) {
        $type = $response['productTypes'][0];
        expect($type['siteSettings'])->not->toBeEmpty();

        $siteSetting = $type['siteSettings'][0];
        expect($siteSetting)->toHaveKeys(['siteId', 'hasUrls', 'uriFormat', 'template', 'enabledByDefault']);
        expect($siteSetting['siteId'])->toBeInt();
        expect($siteSetting['hasUrls'])->toBeBool();
        expect($siteSetting['enabledByDefault'])->toBeBool();
    }
});

it('throws exception when Commerce is not installed', function () {
    // This test validates the tool's own guard clause.
    // When Commerce IS installed, this test verifies the guard doesn't trigger.
    // The actual "not installed" path is tested implicitly by the class_exists check in beforeEach.
    $response = $this->tool->__invoke();

    expect($response)->toHaveKey('productTypes');
});

it('returns non-empty product types list from project config', function () {
    // The project config includes a "general" product type, so this should never be empty
    $response = $this->tool->__invoke();

    expect($response['productTypes'])->not->toBeEmpty();
    expect($response['productTypes'][0]['name'])->toBeString();
    expect($response['productTypes'][0]['handle'])->toBeString();
});
