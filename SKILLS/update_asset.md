# update_asset

Update an existing asset in Craft CMS.

## Route

`PUT /api/assets/<id>`

## Description

Updates an existing asset's metadata (title, filename) and optionally replaces the physical file with a new one. Custom field values can also be updated through this tool.

After updating the asset, always link the user back to the asset in the Craft control panel so they can review the changes in the context of the Craft UI.

## Parameters

### Required Parameters

- **assetId** (integer): The ID of the asset to update

### Optional Parameters

- **title** (string, optional): New title for the asset
- **filename** (string, optional): New filename (will be sanitized by Craft to ensure it's valid)
- **newFileUrl** (string, optional): New file URL to replace the asset file. Supports:
  - Local file paths
  - Local file:// URLs
  - Remote http:// or https:// URLs
- **fieldData** (object, optional): Custom field values to update, keyed by field handle (e.g., `{"alt": "New alt text"}`)

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **assetId** (integer): The asset's ID
- **title** (string): Updated asset title
- **filename** (string): Updated asset filename
- **volumeId** (integer): Volume ID where asset is stored
- **folderId** (integer): Folder ID where asset is located
- **kind** (string): Asset kind (image, video, document, etc.)
- **size** (integer): File size in bytes
- **extension** (string): File extension
- **url** (string): Public URL to the asset
- **cpEditUrl** (string): Craft control panel edit URL

## Example Usage

### Update Asset Title

```json
{
  "assetId": 123,
  "title": "Updated Product Photo"
}
```

### Update Asset Filename

```json
{
  "assetId": 123,
  "filename": "new-filename.jpg"
}
```

### Replace Asset File

```json
{
  "assetId": 123,
  "newFileUrl": "https://example.com/images/new-photo.jpg"
}
```

### Update Custom Fields

```json
{
  "assetId": 123,
  "fieldData": {
    "alt": "A beautiful product photo",
    "caption": "Our flagship product"
  }
}
```

### Update Multiple Properties

```json
{
  "assetId": 123,
  "title": "Hero Image",
  "newFileUrl": "/path/to/new-hero.jpg",
  "fieldData": {
    "alt": "Website hero image"
  }
}
```

## Response Example

```json
{
  "_notes": "The asset was successfully updated.",
  "assetId": 123,
  "title": "Updated Product Photo",
  "filename": "product-photo.jpg",
  "volumeId": 1,
  "folderId": 1,
  "kind": "image",
  "size": 612352,
  "extension": "jpg",
  "url": "https://example.com/assets/product-photo.jpg",
  "cpEditUrl": "https://craft.local/admin/assets/123"
}
```

## Notes

- Only the properties you provide will be updated; others remain unchanged
- When replacing a file, the old file is deleted and replaced with the new one
- The asset ID and volume remain the same even when replacing the file
- File replacement preserves all metadata and field values unless explicitly updated
- Custom field handles must match the asset volume's field layout configuration

## See Also

- **create_asset** - Upload a new asset
- **delete_asset** - Delete an asset
- **get_volumes** - List available asset volumes
