# get_product_type

Get detailed information about a single Commerce product type by ID.

## Tool

`get_product_type` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Returns comprehensive product type details including all configuration properties, per-site URL settings, and full field information for both the product-level and variant-level field layouts. Use this when you need the complete schema for a product type. For an overview of all product types without field details, use `get_product_types` instead.

After retrieving product type information, you can use the product type ID to create new products with `create_product`.

## Parameters

### Required Parameters

- **productTypeId** (integer): The ID of the product type to retrieve.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message
- **id** (integer): Product type ID
- **name** (string): Product type name
- **handle** (string): Product type handle
- **fieldLayoutId** (integer|null): Product-level field layout ID
- **variantFieldLayoutId** (integer|null): Variant-level field layout ID
- **hasDimensions** (boolean): Whether the product type tracks dimensions
- **hasProductTitleField** (boolean): Whether products have a title field
- **productTitleFormat** (string): Auto-generated title format for products
- **productTitleTranslationMethod** (string): How product titles are translated
- **productTitleTranslationKeyFormat** (string|null): Custom translation key format
- **hasVariantTitleField** (boolean): Whether variants have a title field
- **variantTitleFormat** (string): Auto-generated title format for variants
- **variantTitleTranslationMethod** (string): How variant titles are translated
- **variantTitleTranslationKeyFormat** (string|null): Custom translation key format
- **showSlugField** (boolean): Whether slug field is shown
- **slugTranslationMethod** (string): How slugs are translated
- **slugTranslationKeyFormat** (string|null): Custom slug translation key format
- **skuFormat** (string|null): SKU format pattern
- **descriptionFormat** (string): Variant description format
- **template** (string|null): Product page template path
- **maxVariants** (integer): Maximum number of variants
- **enableVersioning** (boolean): Whether versioning is enabled
- **isStructure** (boolean): Whether products use hierarchical structure
- **maxLevels** (integer|null): Maximum hierarchy levels (structure only)
- **defaultPlacement** (string|null): Default placement for new products (structure only)
- **propagationMethod** (string): How content propagates across sites
- **siteSettings** (array): Per-site configuration, each containing:
  - **siteId** (integer): Site ID
  - **hasUrls** (boolean): Whether products have URLs on this site
  - **uriFormat** (string|null): URI format pattern
  - **template** (string|null): Template path
  - **enabledByDefault** (boolean): Whether products are enabled by default
- **productFields** (array): Product-level custom fields with full details
- **variantFields** (array): Variant-level custom fields with full details
- **editUrl** (string): Craft control panel URL for product type settings
- **editVariantUrl** (string): Craft control panel URL for variant settings

## Example Usage

```json
{
  "productTypeId": 1
}
```

## CLI Usage

```bash
agent-craft product-types/get 1
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Throws an error if the product type ID doesn't exist
- Returns full field layout details for both product and variant levels
- Use `get_product_types` to discover available product type IDs

## See Also

- [get_product_types](get_product_types.md) - List all product types
- [create_product_type](create_product_type.md) - Create a new product type
- [update_product_type](update_product_type.md) - Update a product type
- [delete_product_type](delete_product_type.md) - Delete a product type
