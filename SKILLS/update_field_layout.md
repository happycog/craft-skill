# update_field_layout

Update field layout structure and organization.

## Description

Updates field layout structure including tab organization, field assignments, and field requirements.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to update
- **tabs** (array): Updated tab configuration with same structure as `create_field_layout`

## Return Value

Returns updated field layout information.

## Example Usage

```json
{
  "fieldLayoutId": 1,
  "tabs": [
    {
      "name": "Updated Content",
      "fields": [
        {
          "fieldId": 1,
          "required": true
        },
        {
          "fieldId": 2,
          "required": true
        }
      ]
    }
  ]
}
```

## Notes

- Replaces existing tab configuration
- Can reorder fields and tabs
- Updates field requirements per layout
