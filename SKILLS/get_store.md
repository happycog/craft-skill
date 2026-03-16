# get_store

Get detailed information about a single Commerce store by ID.

## Route

`GET /api/stores/<id>`

## Description

Returns a single store's full configuration including checkout settings, currency, pricing strategies, and associated sites. Use this to inspect a specific store's settings before making changes with `update_store`.

## Parameters

### Required Parameters

- **storeId** (integer): The ID of the store to retrieve.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **id** (integer): Store ID
- **name** (string): Store display name
- **handle** (string): Store handle
- **primary** (boolean): Whether this is the primary store
- **currency** (string): 3-letter ISO currency code (e.g. USD, EUR)
- **autoSetNewCartAddresses** (boolean): Whether to auto-set user's primary addresses on new carts
- **autoSetCartShippingMethodOption** (boolean): Whether to auto-set first available shipping method
- **autoSetPaymentSource** (boolean): Whether to auto-set user's primary payment source
- **allowEmptyCartOnCheckout** (boolean): Whether carts can be empty on checkout
- **allowCheckoutWithoutPayment** (boolean): Whether orders can complete without payment
- **allowPartialPaymentOnCheckout** (boolean): Whether partial payments are allowed
- **requireShippingAddressAtCheckout** (boolean): Whether shipping address is required
- **requireBillingAddressAtCheckout** (boolean): Whether billing address is required
- **requireShippingMethodSelectionAtCheckout** (boolean): Whether shipping method selection is required
- **useBillingAddressForTax** (boolean): Whether to use billing address for tax calculations
- **validateOrganizationTaxIdAsVatId** (boolean): Whether to validate tax ID as VAT ID
- **orderReferenceFormat** (string): Order reference number format template
- **freeOrderPaymentStrategy** (string): How free orders are handled ("complete" or "process")
- **minimumTotalPriceStrategy** (string): Minimum total price strategy ("default", "zero", or "shipping")
- **sortOrder** (integer): Sort order
- **sites** (array): Associated sites, each with id, name, handle
- **url** (string): Craft control panel settings URL for the store

## Example Usage

```json
{
  "storeId": 1
}
```

## CLI Usage

```bash
agent-craft stores/get 1
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Throws an error if the store ID doesn't exist
- Returns all checkout, payment, and tax configuration for the store
- Use `get_stores` to discover available store IDs

## See Also

- [get_stores](get_stores.md) - List all stores
- [update_store](update_store.md) - Update store configuration settings
