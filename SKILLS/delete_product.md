# delete_product

Delete a Commerce product and its variants.

## Tool

`delete_product` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Deletes a Commerce product. By default, performs a soft delete where the product is marked as deleted but can be restored. Set `permanentlyDelete` to true to permanently remove the product and all its variants from the database.

## Parameters

### Required Parameters

- **productId** (integer): The ID of the product to delete.

### Optional Parameters

- **permanentlyDelete** (boolean, optional): Set to `true` to permanently delete the product. Default: `false` (soft delete).

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **productId** (integer): Deleted product ID
- **title** (string): Product title
- **slug** (string): Product slug
- **typeId** (integer): Product type ID
- **typeName** (string): Product type name
- **deletedPermanently** (boolean): Whether the product was permanently deleted

## Example Usage

### Soft Delete
```json
{
  "productId": 42
}
```

### Permanent Delete
```json
{
  "productId": 42,
  "permanentlyDelete": true
}
```

### CLI Usage
```bash
agent-craft products/delete 42
agent-craft products/delete 42 --permanentlyDelete=true
```

## Notes

- Soft-deleted products can be restored from the Craft control panel
- Permanently deleted products and their variants cannot be recovered
- Deleting a product also removes all its variants
- Throws an error if the product ID doesn't exist
- Requires Craft Commerce to be installed
