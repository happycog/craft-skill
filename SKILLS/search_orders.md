# search_orders

Search and list Commerce orders with flexible filtering options.

## Route

`GET /api/orders/search`

## Description

Searches for orders in the Craft Commerce system. Returns matching orders with their IDs, numbers, totals, and payment status. Supports filtering by email, order status, completion state, paid status, and date range.

## Parameters

### Optional Parameters

- **query** (string, optional): Search query text.
- **limit** (integer, optional): Maximum number of results to return. Default: 10.
- **email** (string, optional): Filter by customer email address.
- **orderStatusId** (integer, optional): Filter by order status ID.
- **isCompleted** (boolean, optional): Filter by completion state. `true` for completed orders, `false` for active carts.
- **paidStatus** (string, optional): Filter by paid status. Options: `paid`, `unpaid`, `partial`, `overPaid`.
- **dateOrderedAfter** (string, optional): Filter orders placed on or after this date (ISO 8601 format).
- **dateOrderedBefore** (string, optional): Filter orders placed on or before this date (ISO 8601 format).

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the search results
- **results** (array): Array of matching orders, each containing:
  - **orderId** (integer): Order ID
  - **number** (string): Order number
  - **reference** (string|null): Order reference
  - **email** (string): Customer email
  - **isCompleted** (boolean): Whether the order is completed
  - **dateOrdered** (string|null): Date ordered in ISO 8601 format
  - **total** (float): Order total
  - **totalPaid** (float): Total amount paid
  - **paidStatus** (string): Payment status
  - **currency** (string): Currency code
  - **url** (string): Craft control panel edit URL

## Example Usage

### Search by Email
```json
{
  "email": "customer@example.com",
  "limit": 20
}
```

### Filter by Status
```json
{
  "orderStatusId": 1,
  "isCompleted": true,
  "limit": 50
}
```

### Date Range
```json
{
  "dateOrderedAfter": "2025-01-01T00:00:00+00:00",
  "dateOrderedBefore": "2025-03-01T00:00:00+00:00",
  "isCompleted": true
}
```

### Find Unpaid Orders
```json
{
  "paidStatus": "unpaid",
  "isCompleted": true,
  "limit": 100
}
```

## Notes

- Default limit is 10 orders - increase for broader searches
- Combine filters for precise results (e.g., email + date range + status)
- `isCompleted: false` returns active carts, not completed orders
- Paid status filtering is applied after the query for accuracy
- Requires Craft Commerce to be installed
