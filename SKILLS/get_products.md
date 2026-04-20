# get_products

Search and list Commerce products with flexible filtering options.

## Tool

`get_products` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Searches for products in the Craft Commerce system. Returns matching products with their IDs, titles, pricing, and control panel edit URLs. Supports filtering by product type, status, search query, and result limits.

## Parameters

### Optional Parameters

- **query** (string, optional): Search query text to match against product content. If omitted, returns all products (filtered by other parameters).
- **limit** (integer, optional): Maximum number of results to return. Default: 10.
- **status** (string, optional): Product status filter. Options:
  - `live` (default): Published, enabled products
  - `pending`: Scheduled for future publication
  - `expired`: Past expiration date
  - `disabled`: Manually disabled products
- **typeIds** (array of integers, optional): Filter results to specific product types. Only products of these types will be returned.

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the search results
- **results** (array): Array of matching products, each containing:
  - **productId** (integer): Product ID
  - **title** (string): Product title
  - **slug** (string): Product slug
  - **status** (string): Product status
  - **typeId** (integer): Product type ID
  - **defaultSku** (string): Default variant SKU
  - **defaultPrice** (float): Default variant price
  - **url** (string): Craft control panel edit URL

## Example Usage

### Search All Products
```json
{
  "query": "t-shirt",
  "limit": 20
}
```

### Filter by Product Type
```json
{
  "typeIds": [1, 2],
  "limit": 50,
  "status": "live"
}
```

### Get All Live Products
```json
{
  "limit": 100,
  "status": "live"
}
```

## Notes

- Default limit is 10 products - increase for broader searches
- Use `get_product_types` to discover valid product type IDs
- Search query matches against product content, not just titles
- Control panel URLs allow users to quickly navigate to products for editing
- Product type validation ensures provided type IDs exist
- Requires Craft Commerce to be installed
