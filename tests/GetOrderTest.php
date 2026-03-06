<?php

use happycog\craftmcp\tools\GetOrder;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Order::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(GetOrder::class);
});

it('throws exception for non-existent order', function () {
    expect(fn () => $this->tool->__invoke(orderId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Order with ID 99999 not found');
});

it('retrieves order details with expected structure', function () {
    // Create an order programmatically
    $order = new \craft\commerce\elements\Order();
    $order->number = \craft\commerce\elements\Order::generateCartNumber();
    $order->currency = 'USD';
    $order->email = 'test@example.com';
    $order->isCompleted = false;

    $success = Craft::$app->getElements()->saveElement($order);
    expect($success)->toBeTrue();

    $response = $this->tool->__invoke(orderId: $order->id);

    expect($response)->toHaveKeys([
        '_notes',
        'orderId',
        'number',
        'reference',
        'email',
        'isCompleted',
        'dateOrdered',
        'datePaid',
        'currency',
        'couponCode',
        'orderStatusId',
        'orderStatusName',
        'paidStatus',
        'origin',
        'shippingMethodHandle',
        'itemTotal',
        'totalShippingCost',
        'totalDiscount',
        'totalTax',
        'totalPaid',
        'total',
        'lineItems',
        'adjustments',
        'shippingAddress',
        'billingAddress',
        'url',
    ]);
    expect($response['_notes'])->toBe('Retrieved order details.');
    expect($response['orderId'])->toBe($order->id);
    expect($response['email'])->toBe('test@example.com');
    expect($response['currency'])->toBe('USD');
});

it('returns line items as array', function () {
    $order = new \craft\commerce\elements\Order();
    $order->number = \craft\commerce\elements\Order::generateCartNumber();
    $order->currency = 'USD';

    Craft::$app->getElements()->saveElement($order);

    $response = $this->tool->__invoke(orderId: $order->id);

    expect($response['lineItems'])->toBeArray();
    expect($response['adjustments'])->toBeArray();
});

it('returns numeric totals', function () {
    $order = new \craft\commerce\elements\Order();
    $order->number = \craft\commerce\elements\Order::generateCartNumber();
    $order->currency = 'USD';

    Craft::$app->getElements()->saveElement($order);

    $response = $this->tool->__invoke(orderId: $order->id);

    expect($response['itemTotal'])->toBeFloat();
    expect($response['totalShippingCost'])->toBeFloat();
    expect($response['totalDiscount'])->toBeFloat();
    expect($response['totalTax'])->toBeFloat();
    expect($response['totalPaid'])->toBeFloat();
    expect($response['total'])->toBeFloat();
});
