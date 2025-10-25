# update_field

Update existing field properties and settings.

## Route

`PUT /api/fields/<id>`

## Description

Updates existing field properties including name, handle, instructions, and field-type-specific settings.

## Parameters

### Required Parameters

- **fieldId** (integer): The ID of the field to update

### Optional Parameters

- **name** (string, optional): Display name
- **handle** (string, optional): Machine-readable name
- **instructions** (string, optional): Instructions for editors
- **settings** (object, optional): Field-type-specific settings

## Return Value

Returns updated field information.

## Example Usage

### Update Entries Field Sources

```json
{
  "fieldId": 42,
  "settings": {
    "sources": ["section:abc123-4567-89ab-cdef-123456789abc", "section:def456-7890-abcd-ef01-234567890abc"],
    "limit": 10
  }
}
```

## Notes

- Only provided parameters are updated
- Settings are field-type-specific
- Handle changes affect entry data access
- **For Entries fields (`craft\fields\Entries`)**: The `sources` array must contain section UIDs (not IDs) in the format `"section:{uid}"`. Use `get_sections` to retrieve section UIDs. An empty array `[]` allows selection from all sections.
