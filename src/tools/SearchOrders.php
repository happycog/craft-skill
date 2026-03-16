<?php

namespace happycog\craftmcp\tools;

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ElementHelper;
use Illuminate\Support\Collection;

class SearchOrders
{
    /**
     * Search and list Commerce orders with optional filtering.
     *
     * Returns a list of orders matching the given criteria. Supports filtering by
     * email, order status, completion state, paid status, and date range.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        ?string $query = null,
        int $limit = 10,

        /** Filter by customer email address. */
        ?string $email = null,

        /** Filter by order status ID. Use GetOrder or the Commerce CP to find status IDs. */
        ?int $orderStatusId = null,

        /** Filter by completion state. True for completed orders, false for carts. */
        ?bool $isCompleted = null,

        /** Filter by paid status: paid, unpaid, partial, overPaid. */
        ?string $paidStatus = null,

        /** Filter orders placed on or after this date (ISO 8601 format). */
        ?string $dateOrderedAfter = null,

        /** Filter orders placed on or before this date (ISO 8601 format). */
        ?string $dateOrderedBefore = null,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $queryBuilder = Order::find()->limit($limit);

        if ($email !== null) {
            $queryBuilder->email($email);
        }
        if ($orderStatusId !== null) {
            $queryBuilder->orderStatusId($orderStatusId);
        }
        if ($isCompleted !== null) {
            $queryBuilder->isCompleted($isCompleted);
        }
        if ($query !== null) {
            $queryBuilder->search($query);
        }
        if ($dateOrderedAfter !== null) {
            $queryBuilder->dateOrdered('>= ' . $dateOrderedAfter);
        }
        if ($dateOrderedBefore !== null) {
            // If both are set, use the range; otherwise just the before date
            if ($dateOrderedAfter !== null) {
                $queryBuilder->dateOrdered(['and', '>= ' . $dateOrderedAfter, '<= ' . $dateOrderedBefore]);
            } else {
                $queryBuilder->dateOrdered('<= ' . $dateOrderedBefore);
            }
        }

        $result = $queryBuilder->all();

        // Filter by paid status in PHP since it's a computed property
        if ($paidStatus !== null) {
            $result = array_filter($result, function (Order $order) use ($paidStatus) {
                return $order->getPaidStatus() === $paidStatus;
            });
            $result = array_values($result);
        }

        // Generate descriptive notes
        $filters = [];
        if ($query !== null) {
            $filters[] = "search query \"{$query}\"";
        }
        if ($email !== null) {
            $filters[] = "email \"{$email}\"";
        }
        if ($orderStatusId !== null) {
            $status = $commerce->getOrderStatuses()->getOrderStatusById($orderStatusId);
            $filters[] = 'status: ' . ($status?->name ?? "ID {$orderStatusId}");
        }
        if ($isCompleted !== null) {
            $filters[] = $isCompleted ? 'completed orders' : 'active carts';
        }
        if ($paidStatus !== null) {
            $filters[] = "paid status: {$paidStatus}";
        }

        $notesText = empty($filters)
            ? 'The following orders were found.'
            : 'The following orders were found matching ' . implode(' and ', $filters) . '.';

        return [
            '_notes' => $notesText,
            'results' => Collection::make($result)->map(function (Order $order) {
                return [
                    'orderId' => (int) $order->id,
                    'number' => $order->number,
                    'reference' => $order->reference,
                    'email' => $order->email,
                    'isCompleted' => $order->isCompleted,
                    'dateOrdered' => $order->dateOrdered?->format('c'),
                    'total' => (float) $order->getTotal(),
                    'totalPaid' => (float) $order->getTotalPaid(),
                    'paidStatus' => $order->getPaidStatus(),
                    'currency' => $order->currency,
                    'url' => ElementHelper::elementEditorUrl($order),
                ];
            }),
        ];
    }
}
