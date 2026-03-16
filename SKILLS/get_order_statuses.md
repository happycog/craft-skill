# get_order_statuses

List all available Commerce order statuses.

## Route

`GET /api/order-statuses`

## Description

Returns all configured order statuses in Commerce. Order statuses define the workflow stages for orders (e.g., New, Processing, Shipped). Use this to discover valid status IDs before updating an order's status with `update_order`.

## Parameters

None.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **orderStatuses** (array): Array of order status objects, each containing:
  - **id** (integer): Status ID
  - **name** (string): Status display name
  - **handle** (string): Status handle
  - **color** (string): Status color code
  - **description** (string): Status description
  - **isDefault** (boolean): Whether this is the default status for new orders
  - **sortOrder** (integer): Display sort order

## Example Usage

```bash
agent-craft order-statuses/list
```

## Example Response

```json
{
  "_notes": "Retrieved all Commerce order statuses.",
  "orderStatuses": [
    {
      "id": 1,
      "name": "New",
      "handle": "new",
      "color": "green",
      "description": "",
      "isDefault": true,
      "sortOrder": 1
    },
    {
      "id": 2,
      "name": "Processing",
      "handle": "processing",
      "color": "blue",
      "description": "",
      "isDefault": false,
      "sortOrder": 2
    }
  ]
}
```

## Notes

- Use the returned status IDs with `update_order` to change an order's status
- At least one status is always configured as the default
- Requires Craft Commerce to be installed

## See Also

- [update_order](update_order.md) - Update order status
- [get_order](get_order.md) - Retrieve order details
- [search_orders](search_orders.md) - Search orders by status
