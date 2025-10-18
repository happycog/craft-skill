# create_entry_type

Create new entry types with custom handles, names, and field layouts.

## Description

Creates a new entry type in Craft CMS. Entry types define the content schema and field layouts for entries. They can be standalone (useful for Matrix fields) or assigned to sections to control entry structure and behavior.

After creating the entry type, link the user back to the entry type settings in the Craft control panel.

## Parameters

### Required Parameters

- **name** (string): The display name for the entry type

### Optional Parameters

- **handle** (string, optional): Machine-readable name. Auto-generated from name if not provided.
- **hasTitleField** (boolean, optional): Whether entries have title fields. Default: `true`
- **titleTranslationMethod** (string, optional): How titles are translated: `none`, `site`, `language`, or `custom`. Default: `site`
- **titleTranslationKeyFormat** (string, optional): Translation key format for custom title translation. Required when `titleTranslationMethod` is `custom`.
- **titleFormat** (string, optional): Custom title format pattern (e.g., `"{name} - {dateCreated|date}"`). Required when `hasTitleField` is `false`.
- **icon** (string, optional): Icon identifier (e.g., `newspaper`, `image`, `calendar`)
- **color** (string, optional): Color identifier (e.g., `red`, `blue`, `green`, `orange`, `pink`, `purple`, `turquoise`, `yellow`)
- **description** (string, optional): Short description of the entry type's purpose
- **showSlugField** (boolean, optional): Show slug field in admin UI. Default: `true`
- **showStatusField** (boolean, optional): Show status field in admin UI. Default: `true`
- **fieldLayoutId** (integer, optional): Field layout ID to assign

## Return Value

Returns an object containing:

- **entryTypeId** (integer): The newly created entry type's ID
- **name** (string): Entry type name
- **handle** (string): Entry type handle
- **description** (string): Entry type description
- **hasTitleField** (boolean): Has title field
- **titleFormat** (string): Title format
- **icon** (string): Icon identifier
- **color** (string): Color identifier
- **fieldLayoutId** (integer): Associated field layout ID
- **editUrl** (string): Craft control panel URL

## Example Usage

### Basic Entry Type
```json
{
  "name": "Article",
  "handle": "article",
  "icon": "newspaper",
  "color": "blue"
}
```

### Entry Type Without Title Field
```json
{
  "name": "Event",
  "hasTitleField": false,
  "titleFormat": "{eventName} - {eventDate|date}",
  "icon": "calendar",
  "color": "green"
}
```

## Notes

- Entry types can exist independently or be assigned to sections
- Use `create_field_layout` to create field layouts first
- Standalone entry types are commonly used for Matrix field block types
- Title format uses Twig syntax when `hasTitleField` is false
