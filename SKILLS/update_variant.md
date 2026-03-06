# update_variant

Update an existing Commerce product variant.

## Route

`PUT /api/variants/<id>`

## Description

Updates a variant's pricing, SKU, inventory, dimensions, and custom field values. Only the provided fields are updated; all others remain unchanged.

## Parameters

### Required Parameters

- **variantId** (integer): The ID of the variant to update.

### Optional Parameters

- **sku** (string, optional): Variant SKU.
- **price** (float, optional): Variant price.
- **title** (string, optional): Variant title.
- **stock** (integer, optional): Current stock level.
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
- **variantId** (integer): Variant ID
- **title** (string): Updated title
- **sku** (string): Updated SKU
- **price** (float): Updated price
- **stock** (integer): Updated stock level
- **productId** (integer|null): Parent product ID
- **url** (string|null): Parent product's control panel edit URL

## Example Usage

### Update Price and SKU
```json
{
  "variantId": 99,
  "price": 29.99,
  "sku": "WIDGET-LG-BLUE"
}
```

### Update Inventory
```json
{
  "variantId": 99,
  "stock": 150,
  "inventoryTracked": true
}
```

### CLI Usage
```bash
agent-craft variants/update 99 --price=29.99 --sku="WIDGET-LG-BLUE"
agent-craft variants/update 99 --stock=150 --inventoryTracked=true
```

## Notes

- Only provided fields are updated; omitted fields remain unchanged
- The URL links to the parent product's edit page
- Throws an error if the variant ID doesn't exist
- Requires Craft Commerce to be installed
