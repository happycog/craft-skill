# update_entry

Update existing entry content and attributes.

## Route

`PUT /api/entries/<id>`

## Description

Updates an entry in Craft CMS with new field data and attributes. This tool performs direct updates to published entries. For staged changes, prefer using the `create_draft` and `update_draft` tools instead, which allow users to review changes in the Craft UI before accepting them.

After updating the entry, always link the user back to the entry in the Craft control panel so they can review the changes in the context of the Craft UI.

## Parameters

### Required Parameters

- **entryId** (integer): The ID of the entry to update.

### Optional Parameters

- **attributeAndFieldData** (object, optional): JSON object keyed by field handles containing updated attributes and custom field values. Format is identical to `create_entry`:
  - Update title: `{"title": "Updated Title"}`
  - Update multiple fields: `{"title": "New Title", "body": "New content"}`
  - Update custom fields: `{"customFieldHandle": "new value"}`

## Field Data Format

The `attributeAndFieldData` parameter is idempotent - setting a field replaces all its contents with the provided value. If you're updating a field, you must first get the current field contents, modify them, and then pass the entire updated content.

**Native Attributes:**
- `title`: Entry title
- `slug`: URL-friendly identifier
- `postDate`: Publication date (ISO 8601 format)
- `enabled`: Entry enabled status (boolean)

**Custom Fields:**
Use the field handle as the key. Use `get_entry` to retrieve current values and `get_fields` to discover available field handles.

**Matrix Fields:**
Matrix fields contain repeating blocks of content. The format is `fieldHandle: []` where `fieldHandle` is the matrix field's handle and the value is an array of block objects.

Each block object must have:
- `type`: The entry type handle for the block (e.g., "callToAction", "imageBlock")
- `fields`: An object containing the block's field data keyed by field handle

Example with a single block:
```json
{
  "callToActions": [
    {
      "type": "callToAction",
      "fields": {
        "heading": "My Call To Action",
        "buttonText": "Click Here",
        "buttonUrl": "https://example.com"
      }
    }
  ]
}
```

Example with multiple blocks of different types:
```json
{
  "contentBlocks": [
    {
      "type": "textBlock",
      "fields": {
        "heading": "Introduction",
        "body": "Welcome to our site"
      }
    },
    {
      "type": "imageBlock",
      "fields": {
        "image": [123],
        "caption": "A beautiful sunset"
      }
    },
    {
      "type": "textBlock",
      "fields": {
        "heading": "Conclusion",
        "body": "Thank you for reading"
      }
    }
  ]
}
```

**Important:** When updating matrix fields, you must provide the complete array of blocks. The update is idempotent - it replaces all existing blocks with the provided array. To preserve existing blocks while adding new ones, first retrieve the current blocks with `get_entry`, then include them in your update along with the new blocks.

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **entryId** (integer): The updated entry's ID
- **title** (string): Entry title
- **slug** (string): Entry slug
- **postDate** (string): Publication date in ISO 8601 format
- **url** (string): Craft control panel edit URL for the entry

## Example Usage

```json
{
  "entryId": 42,
  "attributeAndFieldData": {
    "title": "Updated Blog Post Title",
    "body": "This is the updated content with new information.",
    "author": "Jane Smith"
  }
}
```

## Notes

- Prefer using draft workflow (`create_draft`, `update_draft`, `apply_draft`) for staged changes
- The tool is idempotent - setting a field replaces all its contents
- Get current field values with `get_entry` before updating
- After update, users can review changes in the Craft control panel using the returned URL
