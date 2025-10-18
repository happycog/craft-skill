# apply_draft

Apply draft changes to the canonical entry, making content live.

## Description

Applies a draft to its canonical entry, making the draft content live. This tool applies all changes from the draft to the canonical entry and removes the draft. The canonical entry will be updated with all content from the draft. This action cannot be undone.

After applying the draft, always link the user back to the entry in the Craft control panel so they can review the changes in the context of the Craft UI.

## Parameters

### Required Parameters

- **draftId** (integer): The draft ID to apply to its canonical entry

## Return Value

Returns an object containing information about the updated canonical entry:

- **_notes** (string): Success message
- **entryId** (integer): The canonical entry's ID
- **title** (string): Entry title
- **slug** (string): Entry slug
- **status** (string): Entry status
- **sectionId** (integer): Section ID
- **sectionName** (string): Section name
- **entryTypeId** (integer): Entry type ID
- **entryTypeName** (string): Entry type name
- **url** (string): Craft control panel edit URL for the canonical entry

## Example Usage

```json
{
  "draftId": 123
}
```

## Notes

- Works with both regular and provisional drafts
- The draft is removed after successful application
- The canonical entry is updated with all draft content
- This action cannot be undone - draft content permanently replaces canonical content
- Always confirm with users before applying critical drafts
- After application, users can review the published entry in the control panel
- Throws error if draft ID doesn't exist or references a published entry
- The draft must be accessible and valid to be applied
