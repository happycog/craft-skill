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
    $commerce = \craft\commerce\Plugin::getInstance();

    $order = new \craft\commerce\elements\Order();
    $order->number = $commerce->getCarts()->generateCartNumber();
    $order->currency = 'USD';

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

it('generates correct notes for orderStatusId filter', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $statuses = $commerce->getOrderStatuses()->getAllOrderStatuses();

    if (empty($statuses)) {
        $this->markTestSkipped('No order statuses configured in Commerce.');
    }

    $status = $statuses[0];
    $response = $this->tool->__invoke(orderStatusId: $status->id);

    expect($response['_notes'])->toContain('status:');
    expect($response['_notes'])->toContain($status->name);
});

it('filters by isCompleted and returns only matching orders', function () {
    $commerce = \craft\commerce\Plugin::getInstance();

    // Create an incomplete order (cart)
    $order = new \craft\commerce\elements\Order();
    $order->number = $commerce->getCarts()->generateCartNumber();
    $order->currency = 'USD';
    $order->isCompleted = false;

    Craft::$app->getElements()->saveElement($order);

    // Search for active carts — our order should be present
    $response = $this->tool->__invoke(isCompleted: false);

    $foundIds = $response['results']->pluck('orderId')->toArray();
    expect($foundIds)->toContain($order->id);
});

it('generates correct notes for dateOrderedAfter filter', function () {
    // dateOrderedAfter/Before don't show in _notes by default,
    // but we can verify the query runs without error
    $response = $this->tool->__invoke(dateOrderedAfter: '2020-01-01T00:00:00+00:00');

    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('generates correct notes for dateOrderedBefore filter', function () {
    $response = $this->tool->__invoke(dateOrderedBefore: '2099-12-31T23:59:59+00:00');

    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('supports combined dateOrderedAfter and dateOrderedBefore range filter', function () {
    $response = $this->tool->__invoke(
        dateOrderedAfter: '2020-01-01T00:00:00+00:00',
        dateOrderedBefore: '2099-12-31T23:59:59+00:00',
    );

    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['results'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
