# delete_draft

Delete drafts without changing their canonical entries.

## Tool

`delete_draft` (MCP tool, also callable via CLI: `agent-craft` — see the CLI section of the README)

## Description

Deletes a draft in Craft CMS while leaving its canonical entry unchanged. By default, performs a soft delete on the draft so it can still be restored through Craft's normal element recovery flows. Set `permanentlyDelete` to true to remove the draft entirely.

## Parameters

### Required Parameters

- **draftId** (integer): The ID of the draft to delete.

### Optional Parameters

- **permanentlyDelete** (boolean, optional): Set to true to permanently delete the draft. Default is false (soft delete).

## Return Value

Returns an object containing:

- **_notes** (string): Success message
- **draftId** (integer): The deleted draft's ID
- **canonicalId** (integer): The canonical entry ID left unchanged
- **title** (string): Draft title
- **slug** (string): Draft slug
- **draftName** (string|null): Draft name
- **draftNotes** (string|null): Draft notes
- **provisional** (boolean): Whether this was a provisional draft
- **siteId** (integer): Draft site ID
- **deletedPermanently** (boolean): Whether the draft was permanently deleted

## Example Usage

### Soft Delete (Default)
```json
{
  "draftId": 123,
  "permanentlyDelete": false
}
```

### Permanent Delete
```json
{
  "draftId": 123,
  "permanentlyDelete": true
}
```

## Notes

- Only the draft is deleted; the canonical entry is left unchanged
- Works with both regular and provisional drafts
- Throws an error if the ID belongs to a published entry instead of a draft
- Permanent deletion cannot be undone
