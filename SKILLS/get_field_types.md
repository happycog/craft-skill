# get_field_types

Discover available field types in the Craft installation.

## Description

Gets a list of all available field types including those added by plugins. Returns field type classes, display names, icons, and descriptions to help with field creation.

## Parameters

No parameters required.

## Return Value

Returns array of field type objects with class names, display names, icons, and descriptions, sorted alphabetically by name.

## Example Usage

```json
{
}
```

## Notes

- Use before creating fields to discover available types
- Includes plugin-provided field types
- Returns only field types selectable by users
