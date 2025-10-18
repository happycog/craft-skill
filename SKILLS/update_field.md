# update_field

Update existing field properties and settings.

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

## Notes

- Only provided parameters are updated
- Settings are field-type-specific
- Handle changes affect entry data access
