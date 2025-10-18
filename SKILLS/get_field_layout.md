# get_field_layout

Retrieve field layout details including tabs and fields.

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

- Shows complete field organization
- Includes field requirements and instructions
- Use to understand entry type schemas
