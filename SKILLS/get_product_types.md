# get_product_types

List all available Commerce product types.

## Route

`GET /api/product-types`

## Description

Returns all product types configured in Craft Commerce. Product types define the structure and fields for products, similar to how entry types define structure for entries. Use this to discover available product types before creating or searching for products.

Returns each product type's configuration including field layout IDs, title field settings, variant settings, and per-site URL configuration.

## Parameters

No parameters required.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the results
- **productTypes** (array): Array of product types, each containing:
  - **id** (integer): Product type ID
  - **name** (string): Product type name
  - **handle** (string): Product type handle
  - **fieldLayoutId** (integer|null): Product-level field layout ID
  - **variantFieldLayoutId** (integer|null): Variant-level field layout ID
  - **hasDimensions** (boolean): Whether the product type tracks dimensions
  - **hasProductTitleField** (boolean): Whether products have a title field
  - **productTitleFormat** (string): Auto-generated title format for products
  - **hasVariantTitleField** (boolean): Whether variants have a title field
  - **variantTitleFormat** (string): Auto-generated title format for variants
  - **skuFormat** (string|null): SKU format pattern, null if manually entered
  - **maxVariants** (integer): Maximum number of variants allowed
  - **siteSettings** (array): Per-site configuration, each containing:
    - **siteId** (integer): Site ID
    - **hasUrls** (boolean): Whether products have URLs on this site
    - **uriFormat** (string|null): URI format pattern
    - **template** (string|null): Template path for rendering
    - **enabledByDefault** (boolean): Whether products are enabled by default

## Example Usage

```json
{}
```

## CLI Usage

```bash
agent-craft product-types/list
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Use product type IDs to filter results in `get_products`
- Product types are configured in the Commerce section of the Craft control panel
- For detailed information including field layouts, use `get_product_type` with a specific ID

## See Also

- [get_product_type](get_product_type.md) - Get detailed product type with field layouts
- [create_product_type](create_product_type.md) - Create a new product type
- [update_product_type](update_product_type.md) - Update a product type
- [delete_product_type](delete_product_type.md) - Delete a product type
