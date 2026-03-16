<?php

use happycog\craftmcp\tools\GetStores;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetStores::class);
});

it('returns stores array with expected structure', function () {
    $response = $this->tool->__invoke();

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['_notes', 'stores']);
    expect($response['_notes'])->toBe('Retrieved all Commerce stores.');
    expect($response['stores'])->toBeArray();
});

it('returns at least one store', function () {
    $response = $this->tool->__invoke();

    expect($response['stores'])->not->toBeEmpty();
});

it('returns correct keys for each store', function () {
    $response = $this->tool->__invoke();

    $store = $response['stores'][0];
    expect($store)->toHaveKeys([
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
});

it('returns correct types for store fields', function () {
    $response = $this->tool->__invoke();

    $store = $response['stores'][0];
    expect($store['id'])->toBeInt();
    expect($store['name'])->toBeString();
    expect($store['handle'])->toBeString();
    expect($store['primary'])->toBeBool();
    expect($store['currency'])->toBeString();
    expect($store['autoSetNewCartAddresses'])->toBeBool();
    expect($store['autoSetCartShippingMethodOption'])->toBeBool();
    expect($store['autoSetPaymentSource'])->toBeBool();
    expect($store['allowEmptyCartOnCheckout'])->toBeBool();
    expect($store['allowCheckoutWithoutPayment'])->toBeBool();
    expect($store['allowPartialPaymentOnCheckout'])->toBeBool();
    expect($store['requireShippingAddressAtCheckout'])->toBeBool();
    expect($store['requireBillingAddressAtCheckout'])->toBeBool();
    expect($store['requireShippingMethodSelectionAtCheckout'])->toBeBool();
    expect($store['useBillingAddressForTax'])->toBeBool();
    expect($store['validateOrganizationTaxIdAsVatId'])->toBeBool();
    expect($store['orderReferenceFormat'])->toBeString();
    expect($store['freeOrderPaymentStrategy'])->toBeString();
    expect($store['minimumTotalPriceStrategy'])->toBeString();
    expect($store['sortOrder'])->toBeInt();
    expect($store['sites'])->toBeArray();
    expect($store['url'])->toBeString();
});

it('includes a primary store', function () {
    $response = $this->tool->__invoke();

    $primaryStores = array_filter($response['stores'], fn ($s) => $s['primary'] === true);

    expect($primaryStores)->not->toBeEmpty('At least one store should be the primary store');
});

it('returns site information for each store', function () {
    $response = $this->tool->__invoke();

    $store = $response['stores'][0];

    if (!empty($store['sites'])) {
        $site = $store['sites'][0];
        expect($site)->toHaveKeys(['id', 'name', 'handle']);
        expect($site['id'])->toBeInt();
        expect($site['name'])->toBeString();
        expect($site['handle'])->toBeString();
    }
});

it('returns valid currency codes', function () {
    $response = $this->tool->__invoke();

    foreach ($response['stores'] as $store) {
        expect(strlen($store['currency']))->toBe(3, 'Currency should be a 3-letter ISO code');
    }
});

it('returns valid strategy values', function () {
    $response = $this->tool->__invoke();

    $store = $response['stores'][0];
    expect($store['freeOrderPaymentStrategy'])->toBeIn(['complete', 'process']);
    expect($store['minimumTotalPriceStrategy'])->toBeIn(['default', 'zero', 'shipping']);
});
