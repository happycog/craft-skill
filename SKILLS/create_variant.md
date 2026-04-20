# create_variant

Create a new variant on an existing Commerce product.

## Tool

`create_variant` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Adds a new variant to the specified product with the given SKU, price, and optional attributes such as dimensions, quantity limits, and shipping settings. The product type must allow multiple variants (maxVariants > 1) for this to add beyond the default variant.

## Parameters

### Required Parameters

- **productId** (integer): The parent product ID.
- **sku** (string): Variant SKU. Must be unique.
- **price** (float): Variant price.

### Optional Parameters

- **title** (string, optional): Variant title.
- **minQty** (integer, optional): Minimum purchase quantity.
- **maxQty** (integer, optional): Maximum purchase quantity.
- **weight** (float, optional): Variant weight.
- **height** (float, optional): Variant height.
- **length** (float, optional): Variant length.
- **width** (float, optional): Variant width.
- **freeShipping** (boolean, optional): Whether the variant qualifies for free shipping.
- **inventoryTracked** (boolean, optional): Whether inventory is tracked for this variant.
- **fields** (object, optional): Custom field data keyed by field handle.

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **variantId** (integer): Created variant ID
- **title** (string): Variant title
- **sku** (string): Variant SKU
- **price** (float): Variant price
- **stock** (integer): Current stock level (read-only, managed via inventory system)
- **productId** (integer): Parent product ID
- **productTitle** (string): Parent product title
- **url** (string): Craft control panel edit URL for the parent product

## Example Usage

### Basic Variant
```json
{
  "productId": 42,
  "sku": "WIDGET-LG",
  "price": 39.99
}
```

### Variant with All Options
```json
{
  "productId": 42,
  "sku": "WIDGET-LG-RED",
  "price": 39.99,
  "title": "Large Red",
  "minQty": 1,
  "maxQty": 10,
  "weight": 2.5,
  "height": 10.0,
  "length": 20.0,
  "width": 15.0,
  "freeShipping": false,
  "inventoryTracked": true
}
```

### CLI Usage
```bash
agent-craft variants/create --productId=42 --sku="WIDGET-LG" --price=39.99
agent-craft variants/create --productId=42 --sku="WIDGET-SM" --price=19.99 --title="Small" --weight=1.0
```

## Notes

- The new variant is appended to the product's existing variants
- Stock is read-only and managed through Commerce's inventory system
- Throws an error if the product ID is invalid
- Requires Craft Commerce to be installed

## See Also

- [get_variant](get_variant.md) - Retrieve variant details
- [update_variant](update_variant.md) - Update variant attributes
- [delete_variant](delete_variant.md) - Delete a variant
- [create_product](create_product.md) - Create a product with default variant
