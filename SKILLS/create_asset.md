# create_asset

Upload a file and create an asset in Craft CMS.

## Route

`POST /api/assets`

## Description

Uploads a file from a local or remote location and creates an asset in Craft CMS. Assets are organized within volumes (storage locations) and can be optionally placed into specific folders within those volumes.

After creating the asset, always link the user back to the asset in the Craft control panel so they can review the upload in the context of the Craft UI.

## Parameters

### Required Parameters

- **fileUrl** (string): The file to upload. Supports:
  - Local file paths (e.g., `/path/to/file.jpg`)
  - Local file:// URLs (e.g., `file:///path/to/file.jpg`)
  - Remote http:// or https:// URLs (e.g., `https://example.com/image.jpg`)
- **volumeId** (integer): The ID of the asset volume to upload to. Use `get_volumes` to discover available volumes.

### Optional Parameters

- **title** (string, optional): Custom title for the asset. Defaults to the filename if not provided.
- **folderId** (integer, optional): The ID of a folder within the volume. If not provided, the asset is placed in the volume root.

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **assetId** (integer): The ID of the newly created asset
- **title** (string): Asset title
- **filename** (string): Asset filename
- **volumeId** (integer): Volume ID where asset is stored
- **folderId** (integer): Folder ID where asset is located
- **kind** (string): Asset kind (image, video, document, etc.)
- **size** (integer): File size in bytes
- **extension** (string): File extension
- **url** (string): Public URL to the asset (if volume has URLs enabled)
- **cpEditUrl** (string): Craft control panel edit URL for the asset

## Example Usage

### Upload from Local File

```json
{
  "fileUrl": "/path/to/photo.jpg",
  "volumeId": 1,
  "title": "Product Photo"
}
```

### Upload from Remote URL

```json
{
  "fileUrl": "https://example.com/images/hero.jpg",
  "volumeId": 1,
  "title": "Hero Image"
}
```

### Upload to Specific Folder

```json
{
  "fileUrl": "/path/to/document.pdf",
  "volumeId": 2,
  "folderId": 5,
  "title": "User Manual"
}
```

## Response Example

```json
{
  "_notes": "The asset was successfully uploaded and created.",
  "assetId": 123,
  "title": "Product Photo",
  "filename": "photo.jpg",
  "volumeId": 1,
  "folderId": 1,
  "kind": "image",
  "size": 524288,
  "extension": "jpg",
  "url": "https://example.com/assets/photo.jpg",
  "cpEditUrl": "https://craft.local/admin/assets/123"
}
```

## Notes

- Files are temporarily downloaded to the project's temp directory before upload
- File validation depends on the volume configuration (allowed file types, size limits, etc.)
- The asset filename may be modified by Craft to avoid conflicts
- Remote file downloads have a 30-second timeout
- After upload, use the returned `assetId` to reference the asset in entry fields

## Using Assets in Entries

After creating an asset, you can reference it in entry asset fields:

```json
{
  "sectionId": 1,
  "entryTypeId": 1,
  "attributeAndFieldData": {
    "title": "My Entry",
    "featuredImage": [123]
  }
}
```

Where `123` is the `assetId` returned from `create_asset`.

## See Also

- **update_asset** - Update asset metadata or replace file
- **delete_asset** - Delete an asset
- **get_volumes** - List available asset volumes
- **create_entry** - Create entries with asset field references
