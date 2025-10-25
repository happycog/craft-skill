# move_element_in_field_layout

Move an element (field or UI element) to a different position within or between tabs.

## Description

Repositions existing fields and UI elements within a field layout. Elements can be moved within the same tab or to a different tab, with precise positioning control.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to modify
- **elementUid** (string): UID of the element to move
- **targetTabName** (string): Name of the tab to move element to
- **position** (object): Positioning configuration
  - **type** (string): Position type - 'before', 'after', 'prepend', or 'append'
  - **elementUid** (string): UID of reference element (required for 'before' and 'after')

## Return Value

Returns the updated field layout with the element in its new position.

## Example Usage

### Move Element to Different Tab

```json
{
  "fieldLayoutId": 1,
  "elementUid": "abc123-def456-789",
  "targetTabName": "Advanced",
  "position": {
    "type": "append"
  }
}
```

### Move Element Before Another

```json
{
  "fieldLayoutId": 1,
  "elementUid": "xyz789-abc123-456",
  "targetTabName": "Content",
  "position": {
    "type": "before",
    "elementUid": "def456-ghi789-012"
  }
}
```

### Move Element to Top of Tab

```json
{
  "fieldLayoutId": 1,
  "elementUid": "field-uid-123",
  "targetTabName": "Content",
  "position": {
    "type": "prepend"
  }
}
```

## Notes

- Use `get_field_layout` to retrieve element UIDs for moving
- Elements can be moved within the same tab or to a different tab
- Moving to a different tab maintains the element's configuration (width, required, etc.)
- Position types:
  - `prepend`: Move to first position in target tab
  - `append`: Move to last position in target tab
  - `before`: Move before a specific element UID
  - `after`: Move after a specific element UID
- Changes are immediately saved to the field layout
- Element UID remains the same after moving
