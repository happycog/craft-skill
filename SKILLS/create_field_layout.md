# create_field_layout

Create field layouts with organized tabs and fields.

## Description

Creates field layouts that define how fields are organized for entry types. Field layouts organize fields into tabs and control field requirements and custom instructions.

## Parameters

### Required Parameters

- **type** (string): Element type (e.g., `craft\elements\Entry`)
- **tabs** (array): Array of tab objects, each containing:
  - `name` (string): Tab name
  - `fields` (array): Array of field objects with:
    - `fieldId` (integer): Field ID
    - `required` (boolean, optional): Whether field is required
    - `instructions` (string, optional): Override field instructions

### Optional Parameters

- **elementId** (integer, optional): Element ID for element-specific layouts

## Return Value

Returns field layout ID and configuration.

## Example Usage

```json
{
  "type": "craft\\elements\\Entry",
  "tabs": [
    {
      "name": "Content",
      "fields": [
        {
          "fieldId": 1,
          "required": true,
          "instructions": "Main content goes here"
        },
        {
          "fieldId": 2,
          "required": false
        }
      ]
    },
    {
      "name": "SEO",
      "fields": [
        {
          "fieldId": 3,
          "required": false
        }
      ]
    }
  ]
}
```

## Notes

- Organizes fields into logical groups (tabs)
- Controls field requirements per layout
- Can override field instructions per layout
- Assign to entry types after creation
