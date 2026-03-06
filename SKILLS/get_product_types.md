# get_product_types

List all available Commerce product types.

## Route

`GET /api/product-types`

## Description

Returns all product types configured in Craft Commerce. Product types define the structure and fields for products, similar to how entry types define structure for entries. Use this to discover available product types before creating or searching for products.

## Parameters

No parameters required.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the results
- **productTypes** (array): Array of product types, each containing:
  - **id** (integer): Product type ID
  - **name** (string): Product type name
  - **handle** (string): Product type handle
  - **hasDimensions** (boolean): Whether the product type tracks dimensions
  - **hasVariantTitleField** (boolean): Whether variants have a title field
  - **maxVariants** (integer): Maximum number of variants allowed (1 means single variant, >1 means multiple variants)

## Example Usage

```json
{}
```

## Notes

- Requires Craft Commerce to be installed and enabled
- Use product type IDs to filter results in `get_products`
- Product types are configured in the Commerce section of the Craft control panel
