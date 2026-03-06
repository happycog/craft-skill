<?php

use happycog\craftmcp\tools\SearchOrders;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Order::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(SearchOrders::class);
});

it('returns orders with expected structure', function () {
    $response = $this->tool->__invoke();

    expect($response)->toBeArray();
    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['_notes'])->toBe('The following orders were found.');
    expect($response['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('respects limit parameter', function () {
    $limit = 2;
    $response = $this->tool->__invoke(limit: $limit);

    expect($response['results']->count())->toBeLessThanOrEqual($limit);
});

it('generates correct notes for search query', function () {
    $response = $this->tool->__invoke(query: 'test-order');

    expect($response['_notes'])->toContain('search query "test-order"');
});

it('generates correct notes for email filter', function () {
    $response = $this->tool->__invoke(email: 'customer@example.com');

    expect($response['_notes'])->toContain('email "customer@example.com"');
});

it('generates correct notes for completed filter', function () {
    $response = $this->tool->__invoke(isCompleted: true);

    expect($response['_notes'])->toContain('completed orders');
});

it('generates correct notes for active carts filter', function () {
    $response = $this->tool->__invoke(isCompleted: false);

    expect($response['_notes'])->toContain('active carts');
});

it('generates correct notes for paid status filter', function () {
    $response = $this->tool->__invoke(paidStatus: 'paid');

    expect($response['_notes'])->toContain('paid status: paid');
});

it('generates correct notes with no filters', function () {
    $response = $this->tool->__invoke();

    expect($response['_notes'])->toBe('The following orders were found.');
});

it('generates correct notes for combined filters', function () {
    $response = $this->tool->__invoke(
        email: 'test@example.com',
        isCompleted: true,
    );

    expect($response['_notes'])->toContain('email "test@example.com"');
    expect($response['_notes'])->toContain('completed orders');
});

it('returns correct keys for each order in results', function () {
    // Create an order to ensure there's at least one result
    $order = new \craft\commerce\elements\Order();
    $order->number = \craft\commerce\elements\Order::generateCartNumber();
    $order->currency = 'USD';
    $order->email = 'search-test@example.com';

    Craft::$app->getElements()->saveElement($order);

    $response = $this->tool->__invoke();

    if ($response['results']->isNotEmpty()) {
        $first = $response['results']->first();
        expect($first)->toHaveKeys([
            'orderId',
            'number',
            'reference',
            'email',
            'isCompleted',
            'dateOrdered',
            'total',
            'totalPaid',
            'paidStatus',
            'currency',
            'url',
        ]);
        expect($first['orderId'])->toBeInt();
    }
});
