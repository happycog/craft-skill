<?php

namespace happycog\craftmcp\tools;

use craft\commerce\Plugin as Commerce;

class GetOrderStatuses
{
    /**
     * List all available Commerce order statuses.
     *
     * Order statuses define the workflow stages for orders (e.g. New, Processing, Shipped).
     * Use this to discover valid status IDs before updating an order's status with UpdateOrder.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $orderStatuses = $commerce->getOrderStatuses()->getAllOrderStatuses();

        $statuses = [];
        foreach ($orderStatuses as $status) {
            $statuses[] = [
                'id' => $status->id,
                'name' => $status->name,
                'handle' => $status->handle,
                'color' => $status->color,
                'description' => $status->description,
                'isDefault' => (bool) $status->default,
                'sortOrder' => $status->sortOrder,
            ];
        }

        return [
            '_notes' => 'Retrieved all Commerce order statuses.',
            'orderStatuses' => $statuses,
        ];
    }
}
