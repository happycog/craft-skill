# get_sections

List all sections or filter by section IDs.

## Description

Gets a list of sections and entry types in Craft CMS. This is essential for creating new entries because you must provide section ID and entry type ID when creating entries. Also useful for understanding the site structure.

Each section has a unique set of custom fields. Check the fields of a section to understand the schema of data you send or receive by passing the `fieldLayoutId` to the `get_fields` tool.

## Parameters

### Optional Parameters

- **sectionIds** (array of integers, optional): List of section IDs to retrieve. If omitted, returns all sections.

## Return Value

Returns an array of section objects, each containing:

- **id** (integer): Section ID
- **handle** (string): Section handle (machine-readable name)
- **name** (string): Section display name
- **type** (string): Section type (`single`, `channel`, or `structure`)
- **entryTypes** (array): Array of entry type objects associated with this section, each containing:
  - `id`: Entry type ID
  - `handle`: Entry type handle
  - `name`: Entry type name
  - `fieldLayoutId`: Associated field layout ID
  - `usedBy`: Usage information showing which sections and Matrix fields use this entry type

## Example Usage

### Get All Sections
```json
{
}
```

### Get Specific Sections
```json
{
  "sectionIds": [1, 3, 5]
}
```

## Example Response

```json
[
  {
    "id": 1,
    "handle": "blogPosts",
    "name": "Blog Posts",
    "type": "channel",
    "entryTypes": [
      {
        "id": 1,
        "handle": "post",
        "name": "Post",
        "fieldLayoutId": 1,
        "usedBy": {
          "sections": [1],
          "matrixFields": []
        }
      },
      {
        "id": 2,
        "handle": "article",
        "name": "Article",
        "fieldLayoutId": 2,
        "usedBy": {
          "sections": [1],
          "matrixFields": []
        }
      }
    ]
  },
  {
    "id": 2,
    "handle": "pages",
    "name": "Pages",
    "type": "structure",
    "entryTypes": [
      {
        "id": 3,
        "handle": "page",
        "name": "Page",
        "fieldLayoutId": 3,
        "usedBy": {
          "sections": [2],
          "matrixFields": []
        }
      }
    ]
  }
]
```

## Notes

- Use this tool before creating entries to get valid section and entry type IDs
- Each entry type has a `fieldLayoutId` - use with `get_fields` to discover custom fields
- Entry types show usage information indicating which sections and Matrix fields reference them
- Section types determine content structure (single, channel, structure)
