# delete_variant

Delete a Commerce product variant.

## Tool

`delete_variant` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Deletes a Commerce product variant. By default, performs a soft delete where the variant is marked as deleted but can be restored. Set `permanentlyDelete` to true to permanently remove the variant.

## Parameters

### Required Parameters

- **variantId** (integer): The ID of the variant to delete.

### Optional Parameters

- **permanentlyDelete** (boolean, optional): Set to `true` to permanently delete the variant. Default: `false` (soft delete).

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **variantId** (integer): Deleted variant ID
- **title** (string): Variant title
- **sku** (string): Variant SKU
- **productId** (integer|null): Parent product ID
- **productTitle** (string|null): Parent product title
- **deletedPermanently** (boolean): Whether the variant was permanently deleted

## Example Usage

### Soft Delete
```json
{
  "variantId": 456
}
```

### Permanent Delete
```json
{
  "variantId": 456,
  "permanentlyDelete": true
}
```

### CLI Usage
```bash
agent-craft variants/delete 456
agent-craft variants/delete 456 --permanentlyDelete=true
```

## Notes

- Soft-deleted variants can be restored from the Craft control panel
- Permanently deleted variants cannot be recovered
- Throws an error if the variant ID doesn't exist
- Requires Craft Commerce to be installed

## See Also

- [get_variant](get_variant.md) - Retrieve variant details
- [update_variant](update_variant.md) - Update variant attributes
- [create_variant](create_variant.md) - Create a new variant
