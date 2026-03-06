# update_order

Update an existing Commerce order's status or message.

## Route

`PUT /api/orders/<id>`

## Description

Updates a Commerce order's status or internal message/notes. Primarily used to change order status (e.g., from "Processing" to "Shipped") or add notes. For safety, only limited administrative fields can be modified.

## Parameters

### Required Parameters

- **orderId** (integer): The ID of the order to update.

### Optional Parameters

- **orderStatusId** (integer, optional): New order status ID. Use the Commerce control panel to find valid status IDs.
- **message** (string, optional): Order message or internal notes.

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **orderId** (integer): Order ID
- **number** (string): Order number
- **reference** (string|null): Order reference
- **orderStatusId** (integer): Updated status ID
- **orderStatusName** (string|null): Updated status name
- **message** (string): Order message
- **url** (string): Craft control panel edit URL

## Example Usage

### Update Order Status
```json
{
  "orderId": 156,
  "orderStatusId": 3
}
```

### Add Order Notes
```json
{
  "orderId": 156,
  "message": "Customer requested expedited shipping"
}
```

### CLI Usage
```bash
agent-craft orders/update 156 --orderStatusId=3
agent-craft orders/update 156 --message="Shipped via FedEx tracking #12345"
```

## Notes

- Only status and message can be updated; line items and pricing are not modifiable
- Validates that the provided order status ID exists
- Throws an error if the order ID doesn't exist
- Returns the control panel URL for review after updating
- Requires Craft Commerce to be installed
