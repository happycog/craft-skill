# delete_entry

Delete entries with soft delete or permanent deletion options.

## Description

Deletes an entry in Craft CMS. By default, performs a soft delete (Craft's standard behavior) where the entry is marked as deleted but remains in the database and can be restored. Set `permanentlyDelete` to true to permanently remove the entry from the database.

## Parameters

### Required Parameters

- **entryId** (integer): The ID of the entry to delete.

### Optional Parameters

- **permanentlyDelete** (boolean, optional): Set to true to permanently delete the entry. Default is false (soft delete). Permanently deleted entries cannot be restored.

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **entryId** (integer): The deleted entry's ID
- **title** (string): Entry title
- **slug** (string): Entry slug
- **sectionId** (integer): Section ID
- **sectionName** (string): Section name
- **postDate** (string): Publication date in ISO 8601 format
- **deletedPermanently** (boolean): Whether the entry was permanently deleted
- **restoreUrl** (string, optional): Craft control panel URL to restore the entry (only included for soft deletes)

## Example Usage

### Soft Delete (Default)
```json
{
  "entryId": 42,
  "permanentlyDelete": false
}
```

### Permanent Delete
```json
{
  "entryId": 42,
  "permanentlyDelete": true
}
```

## Notes

- Soft delete is the default and recommended approach
- Soft deleted entries can be restored from the Craft control panel
- Use the returned `restoreUrl` to navigate to the trashed entries view
- Permanent deletion cannot be undone - use with caution
- Always confirm with users before permanently deleting entries
