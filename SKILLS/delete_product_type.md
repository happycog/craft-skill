# delete_product_type

Delete a Commerce product type with impact analysis and data protection.

## Tool

`delete_product_type` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Deletes a product type from Craft Commerce. This will remove the product type and potentially affect related product data. The tool analyzes impact and provides usage statistics before deletion.

**WARNING**: Deleting a product type with existing products causes permanent data loss. This action cannot be undone. Always get user approval before forcing deletion of product types with content.

## Parameters

### Required Parameters

- **productTypeId** (integer): The ID of the product type to delete.

### Optional Parameters

- **force** (boolean): Force deletion even if products exist. Default: `false`. Requires user approval for product types with content.

## Return Value

Returns an object containing impact analysis:

- **_notes** (string): Success message
- **id** (integer): Deleted product type's ID
- **name** (string): Product type name
- **handle** (string): Product type handle
- **impact** (object): Impact assessment containing:
  - `hasContent` (boolean): Whether product type contains products
  - `productCount` (integer): Number of products

## Example Usage

### Delete Empty Product Type
```json
{
  "productTypeId": 5
}
```

### Force Delete Product Type with Products
```json
{
  "productTypeId": 3,
  "force": true
}
```

## Example Response

```json
{
  "_notes": "The product type was successfully deleted.",
  "id": 3,
  "name": "Old Products",
  "handle": "oldProducts",
  "impact": {
    "hasContent": true,
    "productCount": 12
  }
}
```

## Error Behavior

If product type contains products and `force=false`, the tool throws an error with detailed impact assessment:

```
Product type 'Clothing' contains data and cannot be deleted without force=true.

Impact Assessment:
- Products: 12

Set force=true to proceed with deletion. This action cannot be undone.
```

## CLI Usage

```bash
# Delete empty product type
agent-craft product-types/delete 5

# Force delete
agent-craft product-types/delete 3 --force=true
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Always review impact assessment before deletion
- Product types with products require `force=true` to delete
- Get explicit user approval before forcing deletion
- Deleted product types cannot be recovered
- All products and their variants are permanently deleted when forced

## See Also

- [get_product_type](get_product_type.md) - Get detailed product type info
- [get_product_types](get_product_types.md) - List all product types
- [create_product_type](create_product_type.md) - Create a new product type
- [update_product_type](update_product_type.md) - Update a product type
