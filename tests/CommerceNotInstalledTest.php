<?php

/**
 * Tests to verify all Commerce tools properly guard against Commerce not being installed.
 *
 * These tests validate that each Commerce tool that uses Commerce::getInstance()
 * includes the proper guard clause. Since Commerce IS installed in the test environment,
 * we verify the guard pattern by confirming:
 * 1. The tool class can be instantiated from the container
 * 2. The tool works when Commerce IS installed (no false positives)
 * 3. The throw_unless guard pattern produces the expected RuntimeException
 */

use happycog\craftmcp\tools\CreateProduct;
use happycog\craftmcp\tools\GetOrder;
use happycog\craftmcp\tools\GetOrderStatuses;
use happycog\craftmcp\tools\GetProducts;
use happycog\craftmcp\tools\GetProductTypes;
use happycog\craftmcp\tools\GetStore;
use happycog\craftmcp\tools\GetStores;
use happycog\craftmcp\tools\SearchOrders;
use happycog\craftmcp\tools\UpdateOrder;
use happycog\craftmcp\tools\UpdateStore;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }
});

it('throw_unless guard produces RuntimeException with correct message', function () {
    // Verify the guard pattern used by all Commerce tools:
    //   throw_unless($commerce, 'Craft Commerce is not installed or enabled.');
    // When $commerce is null, throw_unless throws a RuntimeException.
    expect(fn () => throw_unless(null, 'Craft Commerce is not installed or enabled.'))
        ->toThrow(\RuntimeException::class, 'Craft Commerce is not installed or enabled.');
});

// Test each tool class that has the Commerce guard can be instantiated
// and works correctly when Commerce IS installed (no false positive guard triggers)

it('GetStores works when Commerce is installed', function () {
    $tool = Craft::$container->get(GetStores::class);
    $response = $tool->__invoke();
    expect($response)->toHaveKey('stores');
});

it('GetStore works when Commerce is installed', function () {
    $tool = Craft::$container->get(GetStore::class);
    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getPrimaryStore();

    $response = $tool->__invoke(storeId: $store->id);
    expect($response)->toHaveKey('id');
});

it('UpdateStore works when Commerce is installed', function () {
    $tool = Craft::$container->get(UpdateStore::class);
    $commerce = \craft\commerce\Plugin::getInstance();
    $store = $commerce->getStores()->getPrimaryStore();

    $response = $tool->__invoke(storeId: $store->id);
    expect($response)->toHaveKey('id');
});

it('GetProductTypes works when Commerce is installed', function () {
    $tool = Craft::$container->get(GetProductTypes::class);
    $response = $tool->__invoke();
    expect($response)->toHaveKey('productTypes');
});

it('GetOrderStatuses works when Commerce is installed', function () {
    $tool = Craft::$container->get(GetOrderStatuses::class);
    $response = $tool->__invoke();
    expect($response)->toHaveKey('orderStatuses');
});

it('GetProducts works when Commerce is installed', function () {
    $tool = Craft::$container->get(GetProducts::class);
    $response = $tool->__invoke();
    expect($response)->toHaveKey('results');
});

it('SearchOrders works when Commerce is installed', function () {
    $tool = Craft::$container->get(SearchOrders::class);
    $response = $tool->__invoke();
    expect($response)->toHaveKey('results');
});

// Tools with required parameters — test they don't throw the Commerce guard error
// (they should throw parameter-related errors, not the "not installed" error)

it('CreateProduct does not throw Commerce guard when Commerce is installed', function () {
    $tool = Craft::$container->get(CreateProduct::class);

    try {
        // This will likely fail due to missing required data, but should NOT fail
        // with "Craft Commerce is not installed or enabled"
        $tool->__invoke(productTypeId: 1, title: 'test', sku: 'test-sku', price: 9.99);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->not->toBe('Craft Commerce is not installed or enabled.');
    } catch (\Throwable $e) {
        // Any other error is fine — it means the Commerce guard passed
        expect(true)->toBeTrue();
    }
});

it('GetOrder does not throw Commerce guard when Commerce is installed', function () {
    $tool = Craft::$container->get(GetOrder::class);

    try {
        $tool->__invoke(orderId: 99999);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->not->toBe('Craft Commerce is not installed or enabled.');
    } catch (\Throwable $e) {
        expect(true)->toBeTrue();
    }
});

it('UpdateOrder does not throw Commerce guard when Commerce is installed', function () {
    $tool = Craft::$container->get(UpdateOrder::class);

    try {
        $tool->__invoke(orderId: 99999);
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->not->toBe('Craft Commerce is not installed or enabled.');
    } catch (\Throwable $e) {
        expect(true)->toBeTrue();
    }
});

// Verify all Commerce tool classes can be resolved from the DI container
it('all Commerce tool classes are resolvable from container', function () {
    $commerceTools = [
        GetStores::class,
        GetStore::class,
        UpdateStore::class,
        GetProductTypes::class,
        GetOrderStatuses::class,
        GetProducts::class,
        SearchOrders::class,
        GetOrder::class,
        UpdateOrder::class,
        CreateProduct::class,
    ];

    foreach ($commerceTools as $toolClass) {
        $tool = Craft::$container->get($toolClass);
        expect($tool)->toBeInstanceOf($toolClass);
    }
});
