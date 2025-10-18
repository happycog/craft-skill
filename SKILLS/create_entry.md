# create_entry

Create new entries in Craft CMS with custom field data and native attributes.

## Description

Creates a new entry in Craft CMS. An "Entry" in Craft is a generic term that could hold categories, media, and various other data types. Always query sections first to understand what types of entries can be created and use the section definition to determine if the user is requesting an "Entry".

After creating the entry, always link the user back to the entry in the Craft control panel so they can review the changes in the context of the Craft UI.

## Parameters

### Required Parameters

- **sectionId** (integer): The section ID where the entry will be created. Use `get_sections` to discover valid section IDs.
- **entryTypeId** (integer): The entry type ID that defines the content schema. Use `get_sections` or `get_entry_types` to discover valid entry type IDs.

### Optional Parameters

- **siteId** (integer, optional): Site ID for multi-site installations. Defaults to primary site if not provided. Use `get_sites` to discover valid siteId values.
- **attributeAndFieldData** (object, optional): JSON object keyed by field handles containing entry attributes and custom field values. Examples:
  - Set title: `{"title": "My New Entry"}`
  - Set multiple fields: `{"title": "My Entry", "body": "Content here", "slug": "my-entry"}`
  - Set custom fields: `{"customFieldHandle": "value"}`

## Field Data Format

The `attributeAndFieldData` parameter accepts both native Craft attributes and custom fields:

**Native Attributes:**
- `title`: Entry title
- `slug`: URL-friendly identifier
- `postDate`: Publication date (ISO 8601 format)
- `enabled`: Entry enabled status (boolean)

**Custom Fields:**
Use the field handle as the key. For example, if you have a "body" field, pass `{"body": "Content"}`. Use `get_fields` with the entry type's field layout ID to discover available custom fields and their handles.

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **entryId** (integer): The newly created entry's ID
- **title** (string): Entry title
- **slug** (string): Entry slug
- **postDate** (string): Publication date in ISO 8601 format
- **url** (string): Craft control panel edit URL for the entry

## Example Usage

```json
{
  "sectionId": 1,
  "entryTypeId": 2,
  "siteId": 1,
  "attributeAndFieldData": {
    "title": "Welcome to Our Blog",
    "slug": "welcome-to-our-blog",
    "body": "This is the main content of our first blog post.",
    "author": "John Doe",
    "postDate": "2024-01-15T10:00:00Z"
  }
}
```

## Notes

- Always query sections and entry types first to get valid IDs
- Use `get_fields` to understand what custom fields are available for the entry type
- The tool is idempotent - setting a field replaces all its contents with the provided value
- After creation, users can review the entry in the Craft control panel using the returned URL
