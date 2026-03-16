<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ElementHelper;

class UpdateOrder
{
    /**
     * Update an existing Commerce order's status or message.
     *
     * Primarily used to change the order status (e.g., from "Processing" to "Shipped")
     * or update the order message/notes. For safety, only limited fields can be updated.
     *
     * After updating the order, link the user to the order in the Craft control panel
     * so they can review changes.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $orderId,

        /** New order status ID. Use SearchOrders or Commerce CP to find valid status IDs. */
        ?int $orderStatusId = null,

        /** Order message or internal notes. */
        ?string $message = null,
    ): array {
        $order = Craft::$app->getElements()->getElementById($orderId, Order::class);

        throw_unless($order instanceof Order, \InvalidArgumentException::class, "Order with ID {$orderId} not found");

        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        if ($orderStatusId !== null) {
            $statusObj = $commerce->getOrderStatuses()->getOrderStatusById($orderStatusId);
            throw_unless($statusObj, \InvalidArgumentException::class, "Order status with ID {$orderStatusId} not found");
            $order->orderStatusId = $orderStatusId;
        }
        if ($message !== null) {
            $order->message = $message;
        }

        throw_unless(
            Craft::$app->getElements()->saveElement($order),
            "Failed to save order: " . implode(', ', $order->getFirstErrors()),
        );

        // Resolve status name
        $orderStatusName = null;
        if ($order->orderStatusId) {
            $statusObj = $commerce->getOrderStatuses()->getOrderStatusById($order->orderStatusId);
            $orderStatusName = $statusObj?->name;
        }

        return [
            '_notes' => 'The order was successfully updated.',
            'orderId' => $order->id,
            'number' => $order->number,
            'reference' => $order->reference,
            'orderStatusId' => $order->orderStatusId,
            'orderStatusName' => $orderStatusName,
            'message' => $order->message,
            'url' => ElementHelper::elementEditorUrl($order),
        ];
    }
}
