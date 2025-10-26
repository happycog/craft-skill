# delete_site

Delete sites permanently with impact analysis and data protection.

## Route

`DELETE /api/sites/<id>`

## Description

Deletes a site from Craft CMS. This will remove the site and potentially affect related data. The tool analyzes impact and provides usage statistics before deletion.

**WARNING**: Deleting a site with existing entries causes permanent data loss. This action cannot be undone. Always get user approval before forcing deletion of sites with content.

**IMPORTANT**: You cannot delete the primary site. If you need to delete it, first set another site as primary using the UpdateSite tool.

## Parameters

### Required Parameters

- **siteId** (integer): The ID of the site to delete (passed in URL as `<id>`)

### Optional Parameters

- **force** (boolean, optional): Force deletion even if entries exist. Default: `false`. Requires user approval for sites with content.

## Return Value

Returns an object containing impact analysis:

- **id** (integer): Deleted site's ID
- **name** (string): Site name
- **handle** (string): Site handle
- **language** (string): Language code
- **baseUrl** (string): Base URL
- **impact** (object): Impact assessment containing:
  - `hasContent` (boolean): Whether site contains data
  - `entryCount` (integer): Number of entries
  - `draftCount` (integer): Number of drafts
  - `revisionCount` (integer): Number of revisions

## Example Usage

### Delete Empty Site
```json
{
  "siteId": 5
}
```

### Force Delete Site with Content
```json
{
  "siteId": 3,
  "force": true
}
```

## Example Response

```json
{
  "id": 3,
  "name": "Old German Site",
  "handle": "oldGerman",
  "language": "de-DE",
  "baseUrl": "https://example.com/de",
  "impact": {
    "hasContent": true,
    "entryCount": 45,
    "draftCount": 3,
    "revisionCount": 127
  }
}
```

## Error Behavior

### Site with Content (force=false)

If site contains content and `force=false`, the tool throws an error with detailed impact assessment:

```
Site 'German Site' contains data and cannot be deleted without force=true.

Impact Assessment:
- Entries: 45
- Drafts: 3
- Revisions: 127

Set force=true to proceed with deletion. This action cannot be undone.
```

### Primary Site Deletion

If attempting to delete the primary site:

```
Cannot delete the primary site. Set another site as primary first.
```

## Notes

- Always review impact assessment before deletion
- Sites with content require `force=true` to delete
- Get explicit user approval before forcing deletion
- Deleted sites cannot be recovered
- Cannot delete the primary site - set another site as primary first using `update_site`
- All entries, drafts, and revisions for the site are permanently deleted when forced
- Use `get_sites` to identify the primary site and other sites

## See Also

- `create_site` - Create new sites
- `update_site` - Update site properties (including setting primary status)
- `get_sites` - List all sites
