# remove_element_from_field_layout

Remove an element (field or UI element) from a field layout.

## Description

Removes fields or UI elements from a field layout. This removes the element from the layout only - the underlying field definition remains available for use in other layouts.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to modify
- **elementUid** (string): UID of the element to remove

## Return Value

Returns the updated field layout with the element removed.

## Example Usage

### Remove Field from Layout

```json
{
  "fieldLayoutId": 1,
  "elementUid": "abc123-def456-789"
}
```

### Remove UI Element

```json
{
  "fieldLayoutId": 1,
  "elementUid": "heading-uid-123"
}
```

## Notes

- Use `get_field_layout` to retrieve element UIDs
- Removing a field from a layout does NOT delete the field definition
- The field can be added back to this or other layouts later
- Removing a field does not delete data stored in that field
- UI elements (headings, tips, etc.) are permanently removed from the layout
- Changes are immediately saved to the field layout
- Other elements in the layout are preserved and maintain their positions
- **IMPORTANT**: When removing an `EntryTitleField` from an entry type's field layout:
  - The entry type's `hasTitleField` will be automatically set to `false`
  - You **must** then call `update_entry_type` with a `titleFormat` parameter to define how entry titles should be automatically generated
  - Example: `titleFormat: "{dateCreated|date}"` or `titleFormat: "{fieldHandle}"`
  - This is required because entries without title fields need an automatic way to generate titles
