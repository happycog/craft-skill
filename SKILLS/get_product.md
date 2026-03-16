# get_product

Retrieve complete Commerce product details by ID.

## Route

`GET /api/products/<id>`

## Description

Gets detailed information about a specific Commerce product, including all custom fields, native attributes, variant details, and pricing information.

## Parameters

### Required Parameters

- **productId** (integer): The ID of the product to retrieve.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **productId** (integer): Product ID
- **title** (string): Product title
- **slug** (string): Product slug
- **status** (string): Product status (live, pending, expired, disabled)
- **typeId** (integer): Product type ID
- **typeName** (string): Product type name
- **typeHandle** (string): Product type handle
- **postDate** (string|null): Publication date in ISO 8601 format
- **expiryDate** (string|null): Expiry date in ISO 8601 format
- **defaultSku** (string): Default variant SKU
- **defaultPrice** (float): Default variant price
- **url** (string): Craft control panel edit URL
- **variants** (array): Array of variant objects, each containing:
  - **id** (integer): Variant ID
  - **title** (string): Variant title
  - **sku** (string): SKU
  - **price** (float): Price
  - **isDefault** (boolean): Whether this is the default variant
  - **stock** (integer): Current stock level
  - **minQty** (integer|null): Minimum purchase quantity
  - **maxQty** (integer|null): Maximum purchase quantity
  - **weight** (float): Weight
  - **height** (float): Height
  - **length** (float): Length
  - **width** (float): Width
  - **freeShipping** (boolean): Whether variant qualifies for free shipping
  - **inventoryTracked** (boolean): Whether inventory is tracked
  - **sortOrder** (integer): Sort order among variants
- **customFields** (object): Custom field values keyed by field handle

## Example Usage

```json
{
  "productId": 42
}
```

## Notes

- Returns full product data including all variants and custom fields
- Use `get_products` to search/list products if you don't know the ID
- Throws an error if the product ID doesn't exist
- Requires Craft Commerce to be installed
