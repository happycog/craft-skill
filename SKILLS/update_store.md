# update_store

Update a Commerce store's configuration settings.

## Route

`PUT /api/stores/<id>`

## Description

Updates a Commerce store's checkout behavior, pricing strategies, and address requirements. Only the provided settings are updated; all others remain unchanged. Currency cannot be changed after orders have been placed in the store.

## Parameters

### Required Parameters

- **storeId** (integer): The ID of the store to update.

### Optional Parameters

- **name** (string, optional): Store display name.
- **currency** (string, optional): Currency code (e.g. USD, EUR). Cannot be changed after orders are placed.
- **autoSetNewCartAddresses** (boolean, optional): Whether to auto-set the user's primary addresses on new carts.
- **autoSetCartShippingMethodOption** (boolean, optional): Whether to auto-set the first available shipping method on carts.
- **autoSetPaymentSource** (boolean, optional): Whether to auto-set the user's primary payment source on new carts.
- **allowEmptyCartOnCheckout** (boolean, optional): Whether carts are allowed to be empty on checkout.
- **allowCheckoutWithoutPayment** (boolean, optional): Whether orders can be completed without payment.
- **allowPartialPaymentOnCheckout** (boolean, optional): Whether partial payments are allowed from the front end.
- **requireShippingAddressAtCheckout** (boolean, optional): Whether a shipping address is required before payment.
- **requireBillingAddressAtCheckout** (boolean, optional): Whether a billing address is required before payment.
- **requireShippingMethodSelectionAtCheckout** (boolean, optional): Whether shipping method selection is required before payment.
- **useBillingAddressForTax** (boolean, optional): Whether to use the billing address for tax calculations instead of shipping.
- **validateOrganizationTaxIdAsVatId** (boolean, optional): Whether to validate organizationTaxId as a VAT ID.
- **orderReferenceFormat** (string, optional): Order reference number format template (e.g. `{{number[:7]}}`).
- **freeOrderPaymentStrategy** (string, optional): How free orders are handled: `"complete"` (immediately) or `"process"` (via gateway).
- **minimumTotalPriceStrategy** (string, optional): Minimum total price strategy: `"default"`, `"zero"`, or `"shipping"`.

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **id** (integer): Store ID
- **name** (string): Updated store name
- **handle** (string): Store handle
- **primary** (boolean): Whether this is the primary store
- **currency** (string): Currency code
- **url** (string): Craft control panel settings URL

## Example Usage

### Update Store Name
```json
{
  "storeId": 1,
  "name": "US Store"
}
```

### Update Checkout Settings
```json
{
  "storeId": 1,
  "allowCheckoutWithoutPayment": true,
  "requireBillingAddressAtCheckout": true,
  "requireShippingAddressAtCheckout": true
}
```

### Update Pricing Strategy
```json
{
  "storeId": 1,
  "freeOrderPaymentStrategy": "process",
  "minimumTotalPriceStrategy": "zero"
}
```

## CLI Usage

```bash
# Update store name
agent-craft stores/update 1 --name="US Store"

# Update checkout settings
agent-craft stores/update 1 \
  --allowCheckoutWithoutPayment=true \
  --requireBillingAddressAtCheckout=true

# Update pricing strategy
agent-craft stores/update 1 \
  --freeOrderPaymentStrategy=process \
  --minimumTotalPriceStrategy=zero
```

## Notes

- Only provided fields are updated; omitted fields remain unchanged
- Currency cannot be changed after orders have been placed in the store
- Throws an error if the store ID doesn't exist
- Returns the control panel URL for review after updating
- Requires Craft Commerce to be installed and enabled
- Store handle cannot be changed through this tool (use the Craft control panel)

## See Also

- [get_store](get_store.md) - Get store details before updating
- [get_stores](get_stores.md) - List all stores to find store IDs
