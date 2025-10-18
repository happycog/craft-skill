# create_draft

Create drafts either from scratch or from existing published entries.

## Description

Creates a draft in Craft CMS for staged content changes. Drafts allow content creators to work on changes without affecting live content and save work in progress. Can create drafts from scratch (like creating a new entry) or from existing published entries (to modify existing content).

## Parameters

### Creating from Scratch

- **sectionId** (integer, optional): Section ID for the new draft
- **entryTypeId** (integer, optional): Entry type ID for the new draft
- **attributeAndFieldData** (object, optional): Initial field data and attributes

### Creating from Existing Entry

- **canonicalId** (integer, optional): The ID of the published entry to create a draft from. The draft inherits the canonical entry's content.
- **attributeAndFieldData** (object, optional): Field data to override specific fields from the canonical entry

### Draft Options (All Methods)

- **draftName** (string, optional): Name for the draft (defaults to auto-generated name)
- **draftNotes** (string, optional): Notes about the draft
- **provisional** (boolean, optional): Set to true for provisional drafts (auto-save drafts). Default: false
- **siteId** (integer, optional): Site ID. Defaults to primary site. Use `get_sites` to discover valid values.

## Field Data Format

The `attributeAndFieldData` parameter works identically to `create_entry`:

- Native attributes: `title`, `slug`, `postDate`, `enabled`
- Custom fields: Use field handles as keys
- Format: `{"fieldHandle": "value", "title": "Draft Title"}`

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **draftId** (integer): The newly created draft's ID
- **canonicalId** (integer, optional): ID of the canonical entry (if created from existing)
- **title** (string): Draft title
- **draftName** (string): Name of the draft
- **draftNotes** (string): Draft notes
- **isProvisionalDraft** (boolean): Whether this is a provisional draft
- **url** (string): Craft control panel edit URL for the draft

## Example Usage

### Create Draft from Scratch
```json
{
  "sectionId": 1,
  "entryTypeId": 2,
  "siteId": 1,
  "draftName": "New Article Draft",
  "draftNotes": "Initial draft for review",
  "provisional": false,
  "attributeAndFieldData": {
    "title": "Upcoming Product Launch",
    "body": "Draft content here..."
  }
}
```

### Create Draft from Existing Entry
```json
{
  "canonicalId": 42,
  "draftName": "Content Updates",
  "draftNotes": "Updating product description",
  "attributeAndFieldData": {
    "body": "Updated product information..."
  }
}
```

### Create Provisional Draft (Auto-save)
```json
{
  "canonicalId": 42,
  "provisional": true,
  "attributeAndFieldData": {
    "title": "Work in progress..."
  }
}
```

## Notes

- Drafts allow staged content changes without affecting live content
- Provisional drafts are for auto-save functionality
- Use `update_draft` to modify the draft content
- Use `apply_draft` to publish the draft and make it live
- Draft names default to timestamps if not provided
- After creation, users can review the draft in the Craft control panel
