# add_tab_to_field_layout

Add a new tab to a field layout at a specific position.

## Description

Creates a new tab in a field layout with flexible positioning options. Tabs organize fields and UI elements into logical groups in the Craft CMS control panel. The tab must be created before adding fields or UI elements to it.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to modify
- **name** (string): Display name for the new tab
- **position** (object): Positioning configuration
  - **type** (string): Position type - 'before', 'after', 'prepend', or 'append'
  - **tabName** (string): Name of existing tab (required for 'before' and 'after')

## Return Value

Returns the updated field layout with the new tab included.

## Example Usage

### Append Tab to End

```json
{
  "fieldLayoutId": 1,
  "name": "SEO",
  "position": {
    "type": "append"
  }
}
```

### Insert Before Existing Tab

```json
{
  "fieldLayoutId": 1,
  "name": "Featured Content",
  "position": {
    "type": "before",
    "tabName": "Content"
  }
}
```

### Prepend Tab to Beginning

```json
{
  "fieldLayoutId": 1,
  "name": "Quick Settings",
  "position": {
    "type": "prepend"
  }
}
```

## Notes

- Tabs are created empty - use `add_field_to_field_layout` or `add_ui_element_to_field_layout` to populate them
- Tab names must be unique within the field layout
- Position types:
  - `prepend`: Add as first tab
  - `append`: Add as last tab
  - `before`: Insert before a named tab
  - `after`: Insert after a named tab
- Changes are immediately saved to the field layout
