# get_sites

List all available sites in multi-site installations.

## Description

Gets a list of all available sites in the Craft installation. Useful for understanding multi-site structure and discovering valid siteId values for creating and updating content.

## Parameters

No parameters required.

## Return Value

Returns array of site objects containing:

- **id** (integer): Site ID
- **name** (string): Display name
- **handle** (string): Machine-readable handle
- **url** (string): Base URL if configured
- **primary** (boolean): Whether this is the primary site
- **language** (string): Site language code

## Example Usage

```json
{
}
```

## Example Response

```json
[
  {
    "id": 1,
    "name": "English",
    "handle": "default",
    "url": "https://example.com",
    "primary": true,
    "language": "en-US"
  },
  {
    "id": 2,
    "name": "Español",
    "handle": "es",
    "url": "https://example.com/es",
    "primary": false,
    "language": "es-ES"
  }
]
```

## Notes

- Works for both single-site and multi-site installations
- Use site IDs when creating entries or drafts
- Primary site is used as default when siteId not specified
- Returns base URLs for each configured site
