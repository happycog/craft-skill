# create_product

Create a new Commerce product with a default variant.

## Tool

`create_product` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Creates a new Commerce product with the specified product type, title, and default variant (SKU and price). The product is created with a single default variant; use `create_variant` to add additional variants after creation.

## Parameters

### Required Parameters

- **typeId** (integer): The product type ID. Use `get_product_types` to discover available types.
- **title** (string): Product title.
- **sku** (string): SKU for the default variant.
- **price** (float): Price for the default variant.

### Optional Parameters

- **slug** (string, optional): Product slug. Auto-generated from title if not provided.
- **postDate** (string, optional): Post date in ISO 8601 format. Defaults to now.
- **expiryDate** (string, optional): Expiry date in ISO 8601 format. Null means no expiry.
- **enabled** (boolean, optional): Whether the product is enabled. Default: `true`.
- **fields** (object, optional): Custom field data keyed by field handle.

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **productId** (integer): Created product ID
- **title** (string): Product title
- **slug** (string): Product slug
- **status** (string): Product status (live, pending, expired, disabled)
- **typeId** (integer): Product type ID
- **typeName** (string): Product type name
- **defaultSku** (string): Default variant SKU
- **defaultPrice** (float): Default variant price
- **url** (string): Craft control panel edit URL

## Example Usage

### Basic Product
```json
{
  "typeId": 1,
  "title": "Ergonomic Widget",
  "sku": "WIDGET-001",
  "price": 29.99
}
```

### Product with All Options
```json
{
  "typeId": 1,
  "title": "Premium Widget",
  "sku": "WIDGET-PRE-001",
  "price": 99.99,
  "slug": "premium-widget",
  "postDate": "2025-06-01T00:00:00+00:00",
  "expiryDate": "2025-12-31T23:59:59+00:00",
  "enabled": true,
  "fields": {
    "description": "A premium ergonomic widget."
  }
}
```

### CLI Usage
```bash
agent-craft products/create --typeId=1 --title="Ergonomic Widget" --sku="WIDGET-001" --price=29.99
agent-craft products/create --typeId=1 --title="Premium Widget" --sku="WIDGET-PRE" --price=99.99 --slug="premium-widget" --enabled=false
```

## Notes

- A default variant is always created with the provided SKU and price
- Use `get_product_types` to discover available product type IDs
- Use `create_variant` to add additional variants after creation
- Throws an error if the product type ID is invalid
- Requires Craft Commerce to be installed

## See Also

- [get_product_types](get_product_types.md) - Discover available product types
- [update_product](update_product.md) - Update product attributes
- [create_variant](create_variant.md) - Add variants to a product
