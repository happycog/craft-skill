# delete_section

Delete sections permanently with impact analysis and data protection.

## Description

Deletes a section from Craft CMS. This will remove the section and potentially affect related data. The tool analyzes impact and provides usage statistics before deletion.

**WARNING**: Deleting a section with existing entries causes permanent data loss. This action cannot be undone. Always get user approval before forcing deletion of sections with content.

## Parameters

### Required Parameters

- **sectionId** (integer): The ID of the section to delete

### Optional Parameters

- **force** (boolean, optional): Force deletion even if entries exist. Default: `false`. Requires user approval for sections with content.

## Return Value

Returns an object containing impact analysis:

- **id** (integer): Deleted section's ID
- **name** (string): Section name
- **handle** (string): Section handle
- **type** (string): Section type
- **impact** (object): Impact assessment containing:
  - `hasContent` (boolean): Whether section contains data
  - `entryCount` (integer): Number of entries
  - `draftCount` (integer): Number of drafts
  - `revisionCount` (integer): Number of revisions
  - `entryTypeCount` (integer): Number of entry types
  - `entryTypes` (array): List of affected entry types with id, name, and handle

## Example Usage

### Delete Empty Section
```json
{
  "sectionId": 5
}
```

### Force Delete Section with Content
```json
{
  "sectionId": 3,
  "force": true
}
```

## Example Response

```json
{
  "id": 3,
  "name": "Old Blog",
  "handle": "oldBlog",
  "type": "channel",
  "impact": {
    "hasContent": true,
    "entryCount": 45,
    "draftCount": 3,
    "revisionCount": 127,
    "entryTypeCount": 2,
    "entryTypes": [
      {
        "id": 1,
        "name": "Post",
        "handle": "post"
      },
      {
        "id": 2,
        "name": "Article",
        "handle": "article"
      }
    ]
  }
}
```

## Error Behavior

If section contains content and `force=false`, the tool throws an error with detailed impact assessment:

```
Section 'Blog Posts' contains data and cannot be deleted without force=true.

Impact Assessment:
- Entries: 45
- Drafts: 3
- Revisions: 127
- Entry Types: 2

Set force=true to proceed with deletion. This action cannot be undone.
```

## Notes

- Always review impact assessment before deletion
- Sections with content require `force=true` to delete
- Get explicit user approval before forcing deletion
- Deleted sections cannot be recovered
- Entry types associated with the section are also removed
- All entries, drafts, and revisions are permanently deleted when forced
