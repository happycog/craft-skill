<?php

use happycog\craftmcp\tools\UpdateStore;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(UpdateStore::class);

    // Get the primary store for testing
    $commerce = \craft\commerce\Plugin::getInstance();
    $primaryStore = $commerce->getStores()->getPrimaryStore();
    $this->storeId = $primaryStore->id;
    $this->originalName = $primaryStore->getName();
});

it('can update store name', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        name: 'Updated Store Name',
    );

    expect($response['name'])->toBe('Updated Store Name');
    expect($response['_notes'])->toBe('The store was successfully updated.');
});

it('returns proper response format after update', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        name: 'Format Test Store',
    );

    expect($response)->toHaveKeys([
        '_notes',
        'id',
        'name',
        'handle',
        'primary',
        'currency',
        'url',
    ]);
    expect($response['id'])->toBe($this->storeId);
    expect($response['url'])->toBeString();
});

it('can update checkout settings', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        allowCheckoutWithoutPayment: true,
        allowEmptyCartOnCheckout: true,
        allowPartialPaymentOnCheckout: true,
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    // Verify by re-fetching the store
    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect((bool) $store->getAllowCheckoutWithoutPayment())->toBeTrue();
    expect((bool) $store->getAllowEmptyCartOnCheckout())->toBeTrue();
    expect((bool) $store->getAllowPartialPaymentOnCheckout())->toBeTrue();
});

it('can update address requirement settings', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        requireShippingAddressAtCheckout: true,
        requireBillingAddressAtCheckout: true,
        requireShippingMethodSelectionAtCheckout: true,
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect((bool) $store->getRequireShippingAddressAtCheckout())->toBeTrue();
    expect((bool) $store->getRequireBillingAddressAtCheckout())->toBeTrue();
    expect((bool) $store->getRequireShippingMethodSelectionAtCheckout())->toBeTrue();
});

it('can update cart automation settings', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        autoSetNewCartAddresses: true,
        autoSetCartShippingMethodOption: true,
        autoSetPaymentSource: true,
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect((bool) $store->getAutoSetNewCartAddresses())->toBeTrue();
    expect((bool) $store->getAutoSetCartShippingMethodOption())->toBeTrue();
    expect((bool) $store->getAutoSetPaymentSource())->toBeTrue();
});

it('can update tax settings', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        useBillingAddressForTax: true,
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect((bool) $store->getUseBillingAddressForTax())->toBeTrue();
});

it('can update free order payment strategy', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        freeOrderPaymentStrategy: 'process',
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect($store->getFreeOrderPaymentStrategy())->toBe('process');
});

it('can update minimum total price strategy', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        minimumTotalPriceStrategy: 'zero',
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect($store->getMinimumTotalPriceStrategy())->toBe('zero');
});

it('can update order reference format', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        orderReferenceFormat: '{{number[:5]}}',
    );

    expect($response['_notes'])->toBe('The store was successfully updated.');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect($store->getOrderReferenceFormat())->toBe('{{number[:5]}}');
});

it('preserves unchanged settings when updating', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $storeBefore = $commerce->getStores()->getStoreById($this->storeId);
    $originalCurrency = $storeBefore->getCurrency()?->getCode();
    $originalHandle = $storeBefore->handle;

    $this->tool->__invoke(
        storeId: $this->storeId,
        name: 'Only Name Changed',
    );

    $storeAfter = $commerce->getStores()->getStoreById($this->storeId);
    expect($storeAfter->getName())->toBe('Only Name Changed');
    expect($storeAfter->getCurrency()?->getCode())->toBe($originalCurrency);
    expect($storeAfter->handle)->toBe($originalHandle);
});

it('handles empty update gracefully', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
    );

    expect($response['id'])->toBe($this->storeId);
    expect($response['_notes'])->toBe('The store was successfully updated.');
});

it('throws exception for non-existent store', function () {
    expect(fn () => $this->tool->__invoke(storeId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Store with ID 99999 not found');
});

it('can update multiple settings at once', function () {
    $response = $this->tool->__invoke(
        storeId: $this->storeId,
        name: 'Multi-Update Store',
        allowCheckoutWithoutPayment: true,
        requireBillingAddressAtCheckout: true,
        freeOrderPaymentStrategy: 'process',
        minimumTotalPriceStrategy: 'shipping',
    );

    expect($response['name'])->toBe('Multi-Update Store');

    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getStoreById($this->storeId);
    expect((bool) $store->getAllowCheckoutWithoutPayment())->toBeTrue();
    expect((bool) $store->getRequireBillingAddressAtCheckout())->toBeTrue();
    expect($store->getFreeOrderPaymentStrategy())->toBe('process');
    expect($store->getMinimumTotalPriceStrategy())->toBe('shipping');
});
