# delete_entry_type

Delete entry types with usage validation.

## Description

Deletes an entry type from Craft CMS. Validates that the entry type is not in use by sections or Matrix fields before deletion. Requires `force=true` for entry types with existing entries.

## Parameters

### Required Parameters

- **entryTypeId** (integer): The ID of the entry type to delete

### Optional Parameters

- **force** (boolean, optional): Force deletion even if entries exist. Default: `false`. Always get user approval before forcing.

## Return Value

Returns deletion confirmation with usage statistics showing entries, drafts, and revisions removed.

## Notes

- Validates entry type is not in use before deletion
- Force parameter required for entry types with entries
- Deletion removes associated field layouts
- Cannot be undone - get user confirmation
