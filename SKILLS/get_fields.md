# get_fields

List all global fields or fields for a specific field layout.

## Description

Gets a list of all fields in Craft CMS with their configurations and handles. Essential for understanding available fields and their handles when creating or updating entries.

## Parameters

### Optional Parameters

- **fieldLayoutId** (integer, optional): Field layout ID to filter fields. `null` returns all global fields.

## Return Value

Returns array of field objects with handles, types, labels, and configuration.

## Example Usage

### Get All Global Fields
```json
{
}
```

### Get Fields for Layout
```json
{
  "fieldLayoutId": 1
}
```

## Notes

- Returns field handles required for entry creation
- Layout-specific query returns fields in layout order
- Use field handles as keys in entry field data
