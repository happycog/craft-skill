# update_draft

Update draft content and metadata using PATCH semantics.

## Description

Updates an existing draft's content and metadata by draft ID. Works with both regular and provisional drafts without distinction. Uses PATCH semantics - only updates fields that are provided, preserving existing data.

## Parameters

### Required Parameters

- **draftId** (integer): The draft ID to update

### Optional Parameters

- **attributeAndFieldData** (object, optional): JSON object keyed by field handles. Only provided fields are updated, others are preserved. Format:
  - Update single field: `{"body": "Updated content"}`
  - Update multiple fields: `{"title": "New Title", "body": "New content"}`
  - Update custom fields: `{"customFieldHandle": "new value"}`
  
- **draftName** (string, optional): Update the draft name
- **draftNotes** (string, optional): Update the draft notes

## PATCH Semantics

Unlike `update_entry` which is idempotent, this tool uses PATCH semantics:

- Only fields provided in the request are updated
- Unprovided fields retain their existing values
- Allows incremental updates without fetching current state first

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **draftId** (integer): The updated draft's ID
- **canonicalId** (integer, optional): ID of the canonical entry
- **title** (string): Draft title
- **draftName** (string): Draft name
- **draftNotes** (string): Draft notes
- **isProvisionalDraft** (boolean): Whether this is a provisional draft
- **url** (string): Craft control panel edit URL for the draft

## Example Usage

### Update Draft Content
```json
{
  "draftId": 123,
  "attributeAndFieldData": {
    "body": "Updated draft content with new information.",
    "author": "Jane Smith"
  }
}
```

### Update Draft Metadata Only
```json
{
  "draftId": 123,
  "draftName": "Ready for Review",
  "draftNotes": "Content updates complete, ready for editorial review"
}
```

### Update Both Content and Metadata
```json
{
  "draftId": 123,
  "draftName": "Final Draft",
  "draftNotes": "Final version with all changes",
  "attributeAndFieldData": {
    "title": "Updated Article Title",
    "body": "Final content version..."
  }
}
```

## Notes

- Uses PATCH semantics - only provided fields are updated
- Works with both regular and provisional drafts
- No need to fetch current state before updating (unlike `update_entry`)
- Use `apply_draft` to publish changes when ready
- After update, users can review changes in the Craft control panel
- Throws error if draft ID doesn't exist or references a published entry
