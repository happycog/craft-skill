<?php

use happycog\craftmcp\tools\GetOrderStatuses;

beforeEach(function () {
    if (!class_exists(\craft\commerce\Plugin::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetOrderStatuses::class);
});

it('returns order statuses array with expected structure', function () {
    $response = $this->tool->__invoke();

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['_notes', 'orderStatuses']);
    expect($response['_notes'])->toBe('Retrieved all Commerce order statuses.');
    expect($response['orderStatuses'])->toBeArray();
});

it('returns correct keys for each order status', function () {
    $response = $this->tool->__invoke();

    if (!empty($response['orderStatuses'])) {
        $status = $response['orderStatuses'][0];
        expect($status)->toHaveKeys([
            'id',
            'name',
            'handle',
            'color',
            'description',
            'isDefault',
            'sortOrder',
        ]);
        expect($status['id'])->toBeInt();
        expect($status['name'])->toBeString();
        expect($status['handle'])->toBeString();
        expect($status['isDefault'])->toBeBool();
    }
});

it('includes at least one order status', function () {
    // Our project config defines a "New" status, so there should be at least one
    $response = $this->tool->__invoke();

    expect($response['orderStatuses'])->not->toBeEmpty();
});

it('includes the default order status', function () {
    $response = $this->tool->__invoke();

    $defaults = array_filter($response['orderStatuses'], fn ($s) => $s['isDefault'] === true);

    expect($defaults)->not->toBeEmpty('At least one order status should be the default');
});
