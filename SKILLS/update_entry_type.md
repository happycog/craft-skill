# update_entry_type

Update entry type properties and field layout assignments.

## Route

`PUT /api/entry-types/<id>`

## Description

Updates an existing entry type's properties including name, handle, title format, and associated field layout.

## Parameters

### Required Parameters

- **entryTypeId** (integer): The ID of the entry type to update

### Optional Parameters

- **name** (string, optional): Display name
- **handle** (string, optional): Machine-readable name
- **hasTitleField** (boolean, optional): Whether entries of this type have title fields. When changed from true to false, removes the title field from the field layout. When changed from false to true, adds the title field to the field layout. Requires titleFormat when setting to false.
- **titleTranslationMethod** (string, optional): How titles are translated: 'none', 'site', 'language', or 'custom'
- **titleTranslationKeyFormat** (string, optional): Translation key format for custom title translation
- **titleFormat** (string, optional): Title format pattern (e.g., "{name} - {dateCreated|date}"). Required when setting hasTitleField to false.
- **icon** (string, optional): Icon identifier
- **color** (string, optional): Color identifier
- **description** (string, optional): Description
- **showSlugField** (boolean, optional): Whether entries show the slug field in the admin UI
- **showStatusField** (boolean, optional): Whether entries show the status field in the admin UI
- **fieldLayoutId** (integer, optional): Field layout ID to assign

## Return Value

Returns updated entry type information with edit URL.

## Notes

- Only provided parameters are updated
- Can reassign to different field layouts
- When setting `hasTitleField` to false, you must provide a `titleFormat` to define how entry titles are automatically generated
- When setting `hasTitleField` to true, the title field is automatically added to the field layout
- After update, review in Craft control panel

## Example

```json
{
  "entryTypeId": 42,
  "name": "Updated Entry Type",
  "hasTitleField": false,
  "titleFormat": "{dateCreated|date}"
}
```
