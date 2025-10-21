# add_field_to_field_layout

Add a custom field to a field layout at a specific position within a tab.

## Description

Adds a custom field to an existing tab in a field layout with precise positioning control. The field can be configured with display options like width, label overrides, instructions, and requirement settings.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to modify
- **fieldId** (integer): ID of the custom field to add
- **tabName** (string): Name of the tab to add field to (must exist)
- **position** (object): Positioning configuration
  - **type** (string): Position type - 'before', 'after', 'prepend', or 'append'
  - **elementUid** (string): UID of existing element (required for 'before' and 'after')

### Optional Parameters

- **width** (integer): Field width percentage (1-100), defaults to 100
- **required** (boolean): Whether field is required, defaults to false
- **label** (string): Custom field label override
- **instructions** (string): Custom field instructions override
- **tip** (string): Field tip text shown in control panel
- **warning** (string): Field warning text shown in control panel

## Return Value

Returns the updated field layout with the new field included.

## Example Usage

### Append Field to Tab

```json
{
  "fieldLayoutId": 1,
  "fieldId": 5,
  "tabName": "Content",
  "position": {
    "type": "append"
  },
  "width": 100,
  "required": true
}
```

### Insert Field Before Another Element

```json
{
  "fieldLayoutId": 1,
  "fieldId": 8,
  "tabName": "SEO",
  "position": {
    "type": "before",
    "elementUid": "abc123-def456-789"
  },
  "width": 50,
  "label": "Custom Label",
  "instructions": "Enter SEO description"
}
```

### Add Field with Tips and Warnings

```json
{
  "fieldLayoutId": 1,
  "fieldId": 12,
  "tabName": "Advanced",
  "position": {
    "type": "prepend"
  },
  "tip": "This field controls the display behavior",
  "warning": "Changing this may affect published content"
}
```

## Notes

- The target tab must already exist - use `add_tab_to_field_layout` first
- To get element UIDs for positioning, use `get_field_layout` to see existing elements
- Width values are percentages allowing responsive field sizing
- Label and instruction overrides only apply to this specific field layout
- Position types:
  - `prepend`: Add as first element in tab
  - `append`: Add as last element in tab
  - `before`: Insert before a specific element UID
  - `after`: Insert after a specific element UID
- Changes are immediately saved to the field layout
