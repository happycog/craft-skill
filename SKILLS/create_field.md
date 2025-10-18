# create_field

Create new custom fields with specified field types and settings.

## Description

Creates a new custom field in Craft CMS. Fields define content structure and data types for entries. Use `get_field_types` to discover available field types before creating.

## Parameters

### Required Parameters

- **name** (string): Display name for the field
- **type** (string): Field type class (e.g., `craft\fields\PlainText`). Use `get_field_types` to discover available types.

### Optional Parameters

- **handle** (string, optional): Machine-readable name. Auto-generated if not provided.
- **instructions** (string, optional): Instructions for content editors
- **translationMethod** (string, optional): How field content is translated: `none`, `site`, `language`, or `custom`. Default: `site`
- **settings** (object, optional): Field-type-specific settings (varies by field type)

## Return Value

Returns field ID, handle, type, and configuration details.

## Example Usage

```json
{
  "name": "Body Content",
  "handle": "bodyContent",
  "type": "craft\\fields\\PlainText",
  "instructions": "Enter the main body content",
  "settings": {
    "columnType": "text",
    "placeholder": "Enter content here..."
  }
}
```

## Notes

- Use `get_field_types` to discover available field types
- Settings vary by field type
- After creation, assign to field layouts
