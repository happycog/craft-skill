# get_order

Retrieve complete Commerce order details by ID.

## Tool

`get_order` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Gets detailed information about a specific order, including status, totals, line items, adjustments (discounts, shipping, tax), and addresses.

## Parameters

### Required Parameters

- **orderId** (integer): The ID of the order to retrieve.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **orderId** (integer): Order ID
- **number** (string): Order number (unique hash)
- **reference** (string|null): Order reference number
- **email** (string): Customer email
- **isCompleted** (boolean): Whether the order is completed (vs. active cart)
- **dateOrdered** (string|null): Date ordered in ISO 8601 format
- **datePaid** (string|null): Date paid in ISO 8601 format
- **currency** (string): Order currency code
- **couponCode** (string|null): Applied coupon code
- **orderStatusId** (integer|null): Order status ID
- **orderStatusName** (string|null): Order status name
- **paidStatus** (string): Paid status (paid, unpaid, partial, overPaid)
- **origin** (string): Order origin (web, cp)
- **shippingMethodHandle** (string|null): Selected shipping method
- **itemTotal** (float): Sum of line item totals
- **totalShippingCost** (float): Total shipping cost
- **totalDiscount** (float): Total discount amount
- **totalTax** (float): Total tax amount
- **totalPaid** (float): Total amount paid
- **total** (float): Order grand total
- **lineItems** (array): Array of line items, each containing:
  - **id** (integer): Line item ID
  - **description** (string): Item description
  - **sku** (string): Item SKU
  - **qty** (integer): Quantity
  - **price** (float): Unit price
  - **subtotal** (float): Line item subtotal
  - **total** (float): Line item total (with adjustments)
- **adjustments** (array): Array of adjustments (discounts, tax, shipping), each containing:
  - **id** (integer): Adjustment ID
  - **type** (string): Adjustment type
  - **name** (string): Adjustment name
  - **description** (string): Adjustment description
  - **amount** (float): Adjustment amount
  - **included** (boolean): Whether the adjustment is included in the price
- **shippingAddress** (object|null): Shipping address details
- **billingAddress** (object|null): Billing address details
- **url** (string): Craft control panel edit URL

## Example Usage

```json
{
  "orderId": 156
}
```

## Notes

- Returns complete order data including line items, adjustments, and addresses
- Use `search_orders` to find order IDs if you don't know them
- Throws an error if the order ID doesn't exist
- Requires Craft Commerce to be installed
