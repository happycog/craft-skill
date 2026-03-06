# update_product

Update an existing Commerce product's attributes and custom fields.

## Route

`PUT /api/products/<id>`

## Description

Updates a Commerce product's title, slug, dates, enabled state, and custom field values. Only the provided fields are updated; all others remain unchanged.

## Parameters

### Required Parameters

- **productId** (integer): The ID of the product to update.

### Optional Parameters

- **title** (string, optional): New product title.
- **slug** (string, optional): New product slug.
- **postDate** (string, optional): Post date in ISO 8601 format (e.g., `2025-01-01T00:00:00+00:00`).
- **expiryDate** (string, optional): Expiry date in ISO 8601 format, or null to remove.
- **enabled** (boolean, optional): Whether the product is enabled.
- **fields** (object, optional): Custom field data keyed by field handle.

## Return Value

Returns an object containing:

- **_notes** (string): Confirmation message
- **productId** (integer): Product ID
- **title** (string): Updated title
- **slug** (string): Updated slug
- **status** (string): Current status
- **url** (string): Craft control panel edit URL

## Example Usage

### Update Title
```json
{
  "productId": 42,
  "title": "Premium Widget"
}
```

### Update Custom Fields
```json
{
  "productId": 42,
  "fields": {
    "description": "An updated product description",
    "featured": true
  }
}
```

### CLI Usage
```bash
agent-craft products/update 42 --title="Premium Widget" --fields[description]="Updated"
```

## Notes

- Only provided fields are updated; omitted fields remain unchanged
- Variant data is not modified through this tool; use `update_variant` instead
- Throws an error if the product ID doesn't exist
- Returns the control panel URL for review after updating
- Requires Craft Commerce to be installed
