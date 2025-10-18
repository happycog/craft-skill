# update_entry_type

Update entry type properties and field layout assignments.

## Description

Updates an existing entry type's properties including name, handle, title format, and associated field layout.

## Parameters

### Required Parameters

- **entryTypeId** (integer): The ID of the entry type to update

### Optional Parameters

- **name** (string, optional): Display name
- **handle** (string, optional): Machine-readable name
- **titleFormat** (string, optional): Title format pattern
- **icon** (string, optional): Icon identifier
- **color** (string, optional): Color identifier
- **description** (string, optional): Description
- **fieldLayoutId** (integer, optional): Field layout ID to assign

## Return Value

Returns updated entry type information with edit URL.

## Notes

- Only provided parameters are updated
- Can reassign to different field layouts
- After update, review in Craft control panel
