# get_field_layout

Retrieve field layout details including tabs and fields.

## Route

`GET /api/field-layouts`

## Description

Gets field layout information including tab organization and field configuration. Can query by entry type ID, field layout ID, element type, or element ID.

## Parameters

### Query Options (one required)

- **entryTypeId** (integer, optional): Get layout for an entry type
- **fieldLayoutId** (integer, optional): Get layout by ID
- **elementType** (string, optional): Get layout for element type
- **elementId** (integer, optional): Get layout for specific element

## Return Value

Returns field layout structure with tabs and fields organized as configured.

## Example Usage

### By Entry Type
```json
{
  "entryTypeId": 1
}
```

### By Field Layout ID
```json
{
  "fieldLayoutId": 5
}
```

## Notes

- **IMPORTANT**: Always call this tool BEFORE adding tabs, fields, or UI elements to check what already exists
- Shows complete field organization including all existing tabs and their contents
- Use to discover existing tab names before calling `add_field_to_field_layout` or `add_ui_element_to_field_layout`
- Use to check if a tab already exists before calling `add_tab_to_field_layout` to avoid duplicates
- Includes field requirements and instructions
- Use to understand entry type schemas and get element UIDs for positioning
