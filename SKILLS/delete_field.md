# delete_field

Delete custom fields permanently.

## Description

Deletes a custom field from Craft CMS. **WARNING**: Removes field data from all entries using this field. Requires `force=true` if field is in use.

## Parameters

### Required Parameters

- **fieldId** (integer): The ID of the field to delete

### Optional Parameters

- **force** (boolean, optional): Force deletion even if field is in use. Default: `false`.

## Return Value

Returns deletion confirmation.

## Notes

- Removes field data from all entries
- Cannot be undone
- Get user approval before forcing
