# update_product_type

Update an existing Commerce product type's configuration.

## Tool

`update_product_type` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Updates an existing product type in Craft Commerce. Only the provided properties are updated; all others remain unchanged. Allows modification of product type configuration including name, handle, title field settings, variant settings, field layouts, and per-site URL configuration.

After updating the product type, always link the user back to the product type settings in the Craft control panel for review.

## Parameters

### Required Parameters

- **productTypeId** (integer): The ID of the product type to update.

### Optional Parameters

All parameters are optional. Only provided values are updated.

- **name** (string): The display name for the product type
- **handle** (string): Machine-readable name
- **hasProductTitleField** (boolean): Whether products have a title field. If set to `false`, `productTitleFormat` is required.
- **productTitleFormat** (string): Auto-generated title format for products
- **productTitleTranslationMethod** (string): How product titles are translated
- **productTitleTranslationKeyFormat** (string): Custom translation key format
- **hasVariantTitleField** (boolean): Whether variants have a title field. If set to `false`, `variantTitleFormat` is required.
- **variantTitleFormat** (string): Auto-generated title format for variants
- **variantTitleTranslationMethod** (string): How variant titles are translated
- **variantTitleTranslationKeyFormat** (string): Custom variant translation key format
- **showSlugField** (boolean): Whether to show the slug field
- **slugTranslationMethod** (string): How slugs are translated
- **slugTranslationKeyFormat** (string): Custom slug translation key format
- **skuFormat** (string): SKU format pattern
- **descriptionFormat** (string): Variant description format
- **template** (string): Product page template path
- **hasDimensions** (boolean): Whether products track dimensions
- **maxVariants** (integer): Maximum variants per product
- **enableVersioning** (boolean): Whether versioning is enabled
- **isStructure** (boolean): Whether products use hierarchical structure
- **maxLevels** (integer): Maximum hierarchy levels (structure only)
- **defaultPlacement** (string): Where new products are placed (structure only)
- **fieldLayoutId** (integer): Product-level field layout ID
- **variantFieldLayoutId** (integer): Variant-level field layout ID
- **siteSettings** (array): Site-specific settings. Replaces all existing site settings. Each object contains:
  - `siteId` (integer, required): Site ID
  - `enabledByDefault` (boolean): Enable products by default
  - `hasUrls` (boolean): Whether products have URLs
  - `uriFormat` (string): URI format pattern
  - `template` (string): Template path

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **id** (integer): Product type ID
- **name** (string): Product type name
- **handle** (string): Product type handle
- **fieldLayoutId** (integer|null): Product-level field layout ID
- **variantFieldLayoutId** (integer|null): Variant-level field layout ID
- **hasProductTitleField** (boolean): Whether products have a title field
- **productTitleFormat** (string): Product title format
- **hasVariantTitleField** (boolean): Whether variants have a title field
- **variantTitleFormat** (string): Variant title format
- **skuFormat** (string|null): SKU format
- **hasDimensions** (boolean): Whether dimensions are tracked
- **maxVariants** (integer|null): Maximum variants
- **enableVersioning** (boolean): Whether versioning is enabled
- **editUrl** (string): Craft control panel URL for product type settings
- **editVariantUrl** (string): Craft control panel URL for variant settings

## Example Usage

### Update Name
```json
{
  "productTypeId": 1,
  "name": "Updated Product Type"
}
```

### Enable Dimensions and Versioning
```json
{
  "productTypeId": 1,
  "hasDimensions": true,
  "enableVersioning": true,
  "maxVariants": 5
}
```

### Update Title Field Settings
```json
{
  "productTypeId": 1,
  "hasProductTitleField": false,
  "productTitleFormat": "{dateCreated|date}"
}
```

## CLI Usage

```bash
# Update name
agent-craft product-types/update 1 --name="Updated Type"

# Update multiple settings
agent-craft product-types/update 1 \
  --hasDimensions=true \
  --enableVersioning=true \
  --maxVariants=5
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Only provided fields are updated; others remain unchanged
- Throws an error if the product type ID doesn't exist
- Disabling title fields requires providing a title format
- Site settings replace all existing site settings when provided

## See Also

- [get_product_type](get_product_type.md) - Get detailed product type info
- [get_product_types](get_product_types.md) - List all product types
- [create_product_type](create_product_type.md) - Create a new product type
- [delete_product_type](delete_product_type.md) - Delete a product type
