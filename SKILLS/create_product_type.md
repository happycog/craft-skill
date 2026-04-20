# create_product_type

Create a new Commerce product type with configurable fields, variants, and site settings.

## Tool

`create_product_type` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Creates a new product type in Craft Commerce. Product types define the structure, fields, and variant configuration for products — similar to how sections and entry types define structure for entries.

Product types have two independent field layouts:
- **Product-level fields** (`fieldLayoutId`) for product attributes
- **Variant-level fields** (`variantFieldLayoutId`) for variant attributes

Create field layouts first using `create_field_layout`, then assign their IDs here.

Supports multi-site installations with per-site URL settings. If no site settings are provided, the product type will be enabled for all sites with default settings.

After creating the product type, always link the user back to the product type settings in the Craft control panel for further configuration.

## Parameters

### Required Parameters

- **name** (string): The display name for the product type

### Optional Parameters

- **handle** (string): Machine-readable name. Auto-generated from name if not provided.
- **hasProductTitleField** (boolean): Whether products have a title field. Default: `true`. If `false`, `productTitleFormat` is required.
- **productTitleFormat** (string): Auto-generated title format for products when `hasProductTitleField` is false.
- **productTitleTranslationMethod** (string): How product titles are translated. Options: `none`, `site`, `language`, `custom`. Default: `site`
- **productTitleTranslationKeyFormat** (string): Translation key format for custom translation.
- **hasVariantTitleField** (boolean): Whether variants have a title field. Default: `true`. If `false`, `variantTitleFormat` is required.
- **variantTitleFormat** (string): Auto-generated title format for variants when `hasVariantTitleField` is false. Default: `{product.title}`
- **variantTitleTranslationMethod** (string): How variant titles are translated. Default: `site`
- **variantTitleTranslationKeyFormat** (string): Translation key format for custom variant title translation.
- **showSlugField** (boolean): Whether to show the slug field. Default: `true`
- **slugTranslationMethod** (string): How slugs are translated. Default: `site`
- **slugTranslationKeyFormat** (string): Translation key format for custom slug translation.
- **skuFormat** (string): SKU format pattern. If set, SKUs are auto-generated (e.g., `{product.slug}`).
- **descriptionFormat** (string): Variant description format. Default: `{product.title} - {title}`
- **template** (string): Product page template path.
- **hasDimensions** (boolean): Whether products track dimensions. Default: `false`
- **maxVariants** (integer): Maximum variants per product. `null` for unlimited.
- **enableVersioning** (boolean): Whether to enable versioning. Default: `false`
- **isStructure** (boolean): Whether products use hierarchical structure. Default: `false`
- **maxLevels** (integer): Maximum hierarchy levels (structure only). `null` for unlimited.
- **defaultPlacement** (string): Where new products are placed: `beginning` or `end`. Default: `end`
- **fieldLayoutId** (integer): Product-level field layout ID. Create with `create_field_layout` first.
- **variantFieldLayoutId** (integer): Variant-level field layout ID. Create with `create_field_layout` first.
- **siteSettings** (array): Site-specific settings. If not provided, enabled for all sites. Each object contains:
  - `siteId` (integer, required): Site ID
  - `enabledByDefault` (boolean): Enable products by default. Default: `true`
  - `hasUrls` (boolean): Whether products have URLs. Default: `false`
  - `uriFormat` (string): URI format pattern (e.g., `shop/products/{slug}`)
  - `template` (string): Template path for rendering products

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **id** (integer): The newly created product type's ID
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

### Basic Product Type
```json
{
  "name": "Clothing"
}
```

### Product Type with Dimensions and Variants
```json
{
  "name": "Electronics",
  "handle": "electronics",
  "hasDimensions": true,
  "maxVariants": 10,
  "enableVersioning": true,
  "skuFormat": "{product.slug}-{sku}"
}
```

### Product Type with Auto-Generated Titles
```json
{
  "name": "Digital Downloads",
  "hasProductTitleField": false,
  "productTitleFormat": "{dateCreated|date}",
  "hasVariantTitleField": false,
  "variantTitleFormat": "{product.title} - {sku}"
}
```

### Product Type with Site-Specific URLs
```json
{
  "name": "Shop Items",
  "siteSettings": [
    {
      "siteId": 1,
      "enabledByDefault": true,
      "hasUrls": true,
      "uriFormat": "shop/{slug}",
      "template": "shop/products/_entry"
    }
  ]
}
```

## CLI Usage

```bash
# Basic creation
agent-craft product-types/create --name="Clothing"

# With options
agent-craft product-types/create \
  --name="Electronics" \
  --handle="electronics" \
  --hasDimensions=true \
  --maxVariants=10
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Handle is auto-generated from name if not provided
- If `hasProductTitleField` is false, `productTitleFormat` is required
- If `hasVariantTitleField` is false, `variantTitleFormat` is required
- Site settings default to all sites enabled without URLs if not provided
- Field layouts must be created separately using `create_field_layout`
- After creation, configure further in the Craft control panel

## See Also

- [get_product_types](get_product_types.md) - List all product types
- [get_product_type](get_product_type.md) - Get detailed product type info
- [update_product_type](update_product_type.md) - Update a product type
- [delete_product_type](delete_product_type.md) - Delete a product type
- [create_field_layout](create_field_layout.md) - Create field layouts for product/variant fields
