# get_stores

List all Commerce stores with their configuration.

## Route

`GET /api/stores`

## Description

Returns all stores configured in Craft Commerce, including checkout settings, currency, pricing strategies, and associated sites. Use this to discover available stores and their current configuration before updating store settings with `update_store`.

## Parameters

No parameters required.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the results
- **stores** (array): Array of stores, each containing:
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
{}
```

## CLI Usage

```bash
agent-craft stores/list
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Use store IDs when working with store-specific operations
- Each store has its own checkout, payment, and tax configuration
- Stores are managed in the Commerce section of the Craft control panel
- A store can be associated with multiple sites

## See Also

- [get_store](get_store.md) - Get detailed information about a single store
- [update_store](update_store.md) - Update store configuration settings
