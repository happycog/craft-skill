# delete_asset

Delete an asset from Craft CMS.

## Route

`DELETE /api/assets/<id>`

## Description

Permanently deletes an asset element and its associated file from the volume. This action cannot be undone.

**Warning:** Deleting an asset removes the file from storage and any entries or fields referencing this asset will lose the reference.

## Parameters

### Required Parameters

- **assetId** (integer): The ID of the asset to delete

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **assetId** (integer): The ID of the deleted asset
- **title** (string): Title of the deleted asset
- **filename** (string): Filename of the deleted asset
- **volumeId** (integer): Volume ID where the asset was stored
- **kind** (string): Asset kind that was deleted

## Example Usage

### Delete an Asset

```json
{
  "assetId": 123
}
```

## Response Example

```json
{
  "_notes": "The asset was successfully deleted.",
  "assetId": 123,
  "title": "Old Product Photo",
  "filename": "old-photo.jpg",
  "volumeId": 1,
  "kind": "image"
}
```

## Notes

- This operation is permanent and cannot be undone
- The physical file is deleted from the volume storage
- Any relations to this asset in entries are automatically cleaned up by Craft
- Asset fields in entries that referenced this asset will become empty
- Consider creating a backup before deleting important assets

## See Also

- **create_asset** - Upload a new asset
- **update_asset** - Update an existing asset
- **get_volumes** - List available asset volumes
