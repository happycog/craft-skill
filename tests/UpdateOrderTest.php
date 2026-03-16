<?php

use happycog\craftmcp\tools\UpdateOrder;
use craft\commerce\Plugin as Commerce;

beforeEach(function () {
    if (!class_exists(\craft\commerce\elements\Order::class)) {
        $this->markTestSkipped('Craft Commerce is not installed.');
    }

    $this->tool = Craft::$container->get(UpdateOrder::class);

    $commerce = Commerce::getInstance();

    // Create a reusable order for update tests
    $order = new \craft\commerce\elements\Order();
    $order->number = $commerce->getCarts()->generateCartNumber();
    $order->currency = 'USD';

    $success = Craft::$app->getElements()->saveElement($order);
    expect($success)->toBeTrue();

    $this->order = $order;
});

it('can update order message', function () {
    $response = $this->tool->__invoke(
        orderId: $this->order->id,
        message: 'Updated order notes',
    );

    expect($response['message'])->toBe('Updated order notes');
    expect($response['_notes'])->toBe('The order was successfully updated.');
});

it('can update order status', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $statuses = $commerce->getOrderStatuses()->getAllOrderStatuses();

    if (empty($statuses)) {
        $this->markTestSkipped('No order statuses configured in Commerce.');
    }

    $statusId = $statuses[0]->id;

    $response = $this->tool->__invoke(
        orderId: $this->order->id,
        orderStatusId: $statusId,
    );

    expect($response['orderStatusId'])->toBe($statusId);
    expect($response['orderStatusName'])->toBe($statuses[0]->name);
});

it('returns proper response format after update', function () {
    $response = $this->tool->__invoke(
        orderId: $this->order->id,
        message: 'Format test',
    );

    expect($response)->toHaveKeys([
        '_notes',
        'orderId',
        'number',
        'reference',
        'orderStatusId',
        'orderStatusName',
        'message',
        'url',
    ]);
    expect($response['orderId'])->toBe($this->order->id);
    expect($response['url'])->toBeString();
});

it('throws exception for non-existent order', function () {
    expect(fn () => $this->tool->__invoke(orderId: 99999, message: 'test'))
        ->toThrow(\InvalidArgumentException::class, 'Order with ID 99999 not found');
});

it('throws exception for invalid order status ID', function () {
    expect(fn () => $this->tool->__invoke(
        orderId: $this->order->id,
        orderStatusId: 99999,
    ))->toThrow(\InvalidArgumentException::class, 'Order status with ID 99999 not found');
});

it('handles empty update gracefully', function () {
    $response = $this->tool->__invoke(
        orderId: $this->order->id,
    );

    expect($response['orderId'])->toBe($this->order->id);
});

it('can update both status and message at once', function () {
    $commerce = \craft\commerce\Plugin::getInstance();
    $statuses = $commerce->getOrderStatuses()->getAllOrderStatuses();

    if (empty($statuses)) {
        $this->markTestSkipped('No order statuses configured in Commerce.');
    }

    $statusId = $statuses[0]->id;

    $response = $this->tool->__invoke(
        orderId: $this->order->id,
        orderStatusId: $statusId,
        message: 'Status and message updated',
    );

    expect($response['orderStatusId'])->toBe($statusId);
    expect($response['message'])->toBe('Status and message updated');
});
