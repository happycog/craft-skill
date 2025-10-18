# get_entry_types

List all entry types with complete field information and usage stats.

## Description

Gets a list of entry types with complete field layouts, usage statistics, and edit URLs. Shows which sections and Matrix fields reference each entry type.

## Parameters

### Optional Parameters

- **entryTypeIds** (array of integers, optional): List of entry type IDs to retrieve. If omitted, returns all entry types.

## Return Value

Returns an array of entry type objects with complete information including field layouts and usage statistics.

## Example Usage

```json
{
  "entryTypeIds": [1, 2]
}
```

## Notes

- Returns complete field layout information
- Shows usage by sections and Matrix fields
- Use to understand content schemas before creating entries
