<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ElementHelper;

class GetOrder
{
    /**
     * Get detailed information about a single Commerce order by ID.
     *
     * Returns the order's status, totals, line items, addresses, and payment information.
     * Use this after finding orders with SearchOrders to get complete order details.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $orderId,
    ): array {
        $order = Craft::$app->getElements()->getElementById($orderId, Order::class);

        throw_unless($order instanceof Order, \InvalidArgumentException::class, "Order with ID {$orderId} not found");

        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        // Resolve order status name
        $orderStatusName = null;
        if ($order->orderStatusId) {
            $orderStatus = $commerce->getOrderStatuses()->getOrderStatusById($order->orderStatusId);
            $orderStatusName = $orderStatus?->name;
        }

        // Build line items
        $lineItems = [];
        foreach ($order->getLineItems() as $lineItem) {
            $lineItems[] = [
                'id' => $lineItem->id,
                'description' => $lineItem->getDescription(),
                'sku' => $lineItem->getSku(),
                'qty' => $lineItem->qty,
                'price' => (float) $lineItem->price,
                'subtotal' => (float) $lineItem->getSubtotal(),
                'total' => (float) $lineItem->getTotal(),
            ];
        }

        // Build adjustments (discounts, shipping, tax, etc.)
        $adjustments = [];
        foreach ($order->getAdjustments() as $adjustment) {
            $adjustments[] = [
                'id' => $adjustment->id,
                'type' => $adjustment->type,
                'name' => $adjustment->name,
                'description' => $adjustment->description,
                'amount' => (float) $adjustment->amount,
                'included' => $adjustment->included,
            ];
        }

        // Format addresses
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        return [
            '_notes' => 'Retrieved order details.',
            'orderId' => $order->id,
            'number' => $order->number,
            'reference' => $order->reference,
            'email' => $order->email,
            'isCompleted' => $order->isCompleted,
            'dateOrdered' => $order->dateOrdered?->format('c'),
            'datePaid' => $order->datePaid?->format('c'),
            'currency' => $order->currency,
            'couponCode' => $order->couponCode,
            'orderStatusId' => $order->orderStatusId,
            'orderStatusName' => $orderStatusName,
            'paidStatus' => $order->getPaidStatus(),
            'origin' => $order->origin,
            'shippingMethodHandle' => $order->shippingMethodHandle,
            'itemTotal' => (float) $order->getItemTotal(),
            'totalShippingCost' => (float) $order->getTotalShippingCost(),
            'totalDiscount' => (float) $order->getTotalDiscount(),
            'totalTax' => (float) $order->getTotalTax(),
            'totalPaid' => (float) $order->getTotalPaid(),
            'total' => (float) $order->getTotal(),
            'lineItems' => $lineItems,
            'adjustments' => $adjustments,
            'shippingAddress' => $shippingAddress?->toArray(),
            'billingAddress' => $billingAddress?->toArray(),
            'url' => ElementHelper::elementEditorUrl($order),
        ];
    }
}
