# get_volumes

Get information about asset volumes in Craft CMS.

## Route

`GET /api/volumes`

## Description

Retrieves information about all available asset volumes in Craft CMS. Asset volumes define where uploaded files are stored, such as local filesystem, Amazon S3, or other storage services.

Use this tool to discover available volumes before uploading assets with the `create_asset` tool.

## Parameters

None.

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **volumes** (array): Array of volume objects, each containing:
  - **id** (integer): Volume ID
  - **name** (string): Volume name
  - **handle** (string): Volume handle (machine name)
  - **type** (string): Volume type class (e.g., `craft\volumes\Local`)
  - **hasUrls** (boolean): Whether the volume provides public URLs
  - **url** (string|null): Base URL for assets in this volume (if URLs are enabled)

## Example Usage

### Get All Volumes

```json
{}
```

## Response Example

```json
{
  "_notes": "Retrieved all asset volumes.",
  "volumes": [
    {
      "id": 1,
      "name": "Images",
      "handle": "images",
      "type": "craft\\volumes\\Local",
      "hasUrls": true,
      "url": "https://example.com/assets/images"
    },
    {
      "id": 2,
      "name": "Documents",
      "handle": "documents",
      "type": "craft\\volumes\\Local",
      "hasUrls": true,
      "url": "https://example.com/assets/documents"
    },
    {
      "id": 3,
      "name": "Private Files",
      "handle": "privateFiles",
      "type": "craft\\volumes\\Local",
      "hasUrls": false,
      "url": null
    }
  ]
}
```

## Notes

- Volumes without URLs (hasUrls: false) are typically used for private files
- Each volume can have different access permissions and file type restrictions
- Volume IDs are required when creating or managing assets
- Volume types can include Local, Amazon S3, Google Cloud Storage, and others

## See Also

- **create_asset** - Upload a new asset to a volume
- **update_asset** - Update an existing asset
- **delete_asset** - Delete an asset
