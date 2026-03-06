# get_variant

Retrieve complete Commerce variant details by ID.

## Route

`GET /api/variants/<id>`

## Description

Gets detailed information about a specific product variant, including pricing, SKU, inventory, dimensions, and custom fields. Also includes parent product information.

## Parameters

### Required Parameters

- **variantId** (integer): The ID of the variant to retrieve.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **variantId** (integer): Variant ID
- **title** (string): Variant title
- **sku** (string): Variant SKU
- **price** (float): Variant price
- **isDefault** (boolean): Whether this is the default variant
- **sortOrder** (integer): Sort order among variants
- **stock** (integer): Current stock level
- **minQty** (integer|null): Minimum purchase quantity
- **maxQty** (integer|null): Maximum purchase quantity
- **weight** (float): Variant weight
- **height** (float): Variant height
- **length** (float): Variant length
- **width** (float): Variant width
- **freeShipping** (boolean): Whether variant qualifies for free shipping
- **inventoryTracked** (boolean): Whether inventory is tracked
- **productId** (integer|null): Parent product ID
- **productTitle** (string|null): Parent product title
- **url** (string|null): Parent product's control panel edit URL
- **customFields** (object): Custom field values keyed by field handle

## Example Usage

```json
{
  "variantId": 99
}
```

## Notes

- Returns variant data including its parent product context
- Use `get_product` to see all variants of a product at once
- The URL links to the parent product's edit page (variants are edited within the product)
- Throws an error if the variant ID doesn't exist
- Requires Craft Commerce to be installed
