<?php

use happycog\craftmcp\tools\GetStore;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetStore::class);

    // Get the primary store ID for testing
    $commerce = \craft\commerce\Plugin::getInstance();
    $primaryStore = $commerce->getStores()->getPrimaryStore();
    $this->storeId = $primaryStore->id;
});

it('returns store details with expected structure', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response)->toBeArray();
    expect($response)->toHaveKeys([
        '_notes',
        'id',
        'name',
        'handle',
        'primary',
        'currency',
        'autoSetNewCartAddresses',
        'autoSetCartShippingMethodOption',
        'autoSetPaymentSource',
        'allowEmptyCartOnCheckout',
        'allowCheckoutWithoutPayment',
        'allowPartialPaymentOnCheckout',
        'requireShippingAddressAtCheckout',
        'requireBillingAddressAtCheckout',
        'requireShippingMethodSelectionAtCheckout',
        'useBillingAddressForTax',
        'validateOrganizationTaxIdAsVatId',
        'orderReferenceFormat',
        'freeOrderPaymentStrategy',
        'minimumTotalPriceStrategy',
        'sortOrder',
        'sites',
        'url',
    ]);
    expect($response['_notes'])->toBe('Retrieved store details.');
});

it('returns correct types for store fields', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response['id'])->toBeInt();
    expect($response['name'])->toBeString();
    expect($response['handle'])->toBeString();
    expect($response['primary'])->toBeBool();
    expect($response['currency'])->toBeString();
    expect($response['autoSetNewCartAddresses'])->toBeBool();
    expect($response['autoSetCartShippingMethodOption'])->toBeBool();
    expect($response['autoSetPaymentSource'])->toBeBool();
    expect($response['allowEmptyCartOnCheckout'])->toBeBool();
    expect($response['allowCheckoutWithoutPayment'])->toBeBool();
    expect($response['allowPartialPaymentOnCheckout'])->toBeBool();
    expect($response['requireShippingAddressAtCheckout'])->toBeBool();
    expect($response['requireBillingAddressAtCheckout'])->toBeBool();
    expect($response['requireShippingMethodSelectionAtCheckout'])->toBeBool();
    expect($response['useBillingAddressForTax'])->toBeBool();
    expect($response['validateOrganizationTaxIdAsVatId'])->toBeBool();
    expect($response['orderReferenceFormat'])->toBeString();
    expect($response['freeOrderPaymentStrategy'])->toBeString();
    expect($response['minimumTotalPriceStrategy'])->toBeString();
    expect($response['sortOrder'])->toBeInt();
    expect($response['sites'])->toBeArray();
    expect($response['url'])->toBeString();
});

it('returns the correct store by ID', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response['id'])->toBe($this->storeId);
});

it('returns primary store details matching project config', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response['primary'])->toBeTrue();
    expect($response['handle'])->toBe('primary');
    expect($response['currency'])->toBe('USD');
});

it('returns site information for the store', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    if (!empty($response['sites'])) {
        $site = $response['sites'][0];
        expect($site)->toHaveKeys(['id', 'name', 'handle']);
        expect($site['id'])->toBeInt();
        expect($site['name'])->toBeString();
        expect($site['handle'])->toBeString();
    }
});

it('returns valid strategy values', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response['freeOrderPaymentStrategy'])->toBeIn(['complete', 'process']);
    expect($response['minimumTotalPriceStrategy'])->toBeIn(['default', 'zero', 'shipping']);
});

it('returns a control panel settings URL', function () {
    $response = $this->tool->__invoke(storeId: $this->storeId);

    expect($response['url'])->toContain('commerce/store-management/');
});

it('throws exception for non-existent store', function () {
    expect(fn () => $this->tool->__invoke(storeId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Store with ID 99999 not found');
});
