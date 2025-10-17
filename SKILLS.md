---
name: Craft CMS Section Management
description: Manage sections in Craft CMS, including creating, reading, updating, and deleting sections that define the structural organization of content.
---

# Craft CMS Section Management

Sections in Craft CMS define how content is organized and structured. This skill provides HTTP endpoints to manage sections including creation, retrieval, updating, and deletion. Sections support three types: Single (one entry per section), Channel (multiple entries with flexible structure), and Structure (hierarchical entries with parent-child relationships).

## Querying Current Sections

**For LLM Assistant**: When asked about current sections in this Craft instance, query the sections list endpoint first to get the actual state:

```bash
curl http://craft-mcp.dev.markhuot.com/api/sections
```

This will return the authoritative list of sections currently configured in the Craft CMS instance. Always perform this query to provide accurate information about existing sections rather than referencing outdated documentation.

## Base URL

All endpoints are prefixed with the configured API prefix (default: `/api`).

## Endpoints

### Create a Section

Create a new section in Craft CMS.

**Request:**
```
POST /api/sections
Content-Type: application/json

{
  "name": "Blog Posts",
  "type": "channel",
  "entryTypeIds": [1, 2],
  "handle": "blogPosts",
  "enableVersioning": true,
  "propagationMethod": "all",
  "siteSettings": [
    {
      "siteId": 1,
      "enabledByDefault": true,
      "hasUrls": true,
      "uriFormat": "blog/{slug}",
      "template": "blog/post.html"
    }
  ]
}
```

**Parameters:**
- `name` (string, required): Display name for the section
- `type` (string, required): Section type: `single`, `channel`, or `structure`
- `entryTypeIds` (array of integers, required): Entry type IDs to assign to this section. Can be an empty array to create a section without entry types (uncommon but possible).
- `handle` (string, optional): Machine-readable name (auto-generated from name if omitted)
- `enableVersioning` (boolean, optional): Enable entry versioning. Default: `true`
- `propagationMethod` (string, optional): How content propagates across sites: `all`, `siteGroup`, `language`, `custom`, or `none`. Default: `all`
- `maxLevels` (integer, optional): Maximum hierarchy levels for structure sections. `null` or `0` for unlimited. Default: `null` (structure sections only)
- `defaultPlacement` (string, optional): Where new entries are placed: `beginning` or `end`. Default: `end` (structure sections only)
- `maxAuthors` (integer, optional): Maximum number of authors per entry. Default: `null`
- `siteSettings` (array of objects, optional): Site-specific settings for multi-site installations. If not provided, section will be enabled for all sites with default settings. Each object should contain:
  - `siteId` (integer, required): The site ID
  - `enabledByDefault` (boolean, optional): Whether entries are enabled by default for this site. Default: `true`
  - `hasUrls` (boolean, optional): Whether entries have URLs. Default: `true`
  - `uriFormat` (string, optional): URI format pattern. Default: `{handle}` for single sections, `{handle}/{slug}` for channel/structure sections
  - `template` (string, optional): Template path for rendering entries. Default: `null`

**Response:**
```json
{
  "sectionId": 1,
  "name": "Blog Posts",
  "handle": "blogPosts",
  "type": "channel",
  "propagationMethod": "all",
  "maxLevels": null,
  "maxAuthors": null,
  "editUrl": "https://example.com/admin/settings/sections/1"
}
```

### List Sections

Retrieve all sections or filter by section IDs.

**Request:**
```
GET /api/sections?sectionIds=1,2,3
```

**Parameters:**
- `sectionIds` (string, optional): Comma-separated list of section IDs to retrieve. If omitted, returns all sections.

**Response:**
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
      }
    ]
  }
]
```

### Update a Section

Update an existing section's properties and configuration.

**Request:**
```
PUT /api/sections/1
Content-Type: application/json

{
  "name": "News Articles",
  "enableVersioning": false,
  "maxAuthors": 2,
  "siteSettings": [
    {
      "siteId": 1,
      "enabledByDefault": true,
      "hasUrls": true,
      "uriFormat": "news/{slug}",
      "template": "news/article.html"
    }
  ]
}
```

**Parameters:**
- `id` (integer, required, in URL): The section ID to update
- `name` (string, optional): Updated display name
- `handle` (string, optional): Updated machine-readable name
- `type` (string, optional): Section type (single, channel, or structure). Type changes have restrictions based on existing data.
- `entryTypeIds` (array of integers, optional): Entry type IDs to assign to this section
- `enableVersioning` (boolean, optional): Enable or disable entry versioning
- `propagationMethod` (string, optional): How content propagates across sites
- `maxLevels` (integer, optional): Maximum hierarchy levels (structure sections only)
- `defaultPlacement` (string, optional): Where new entries are placed (structure sections only)
- `maxAuthors` (integer, optional): Maximum number of authors per entry
- `siteSettings` (array, optional): Updated site-specific settings

**Response:**
```json
{
  "sectionId": 1,
  "name": "News Articles",
  "handle": "blogPosts",
  "type": "channel",
  "propagationMethod": "all",
  "maxLevels": null,
  "maxAuthors": 2,
  "editUrl": "https://example.com/admin/settings/sections/1"
}
```

### Delete a Section

Delete a section from Craft CMS. Sections with existing entries require the `force` parameter.

**Request:**
```
DELETE /api/sections/1
Content-Type: application/json

{
  "force": false
}
```

**Parameters:**
- `id` (integer, required, in URL): The section ID to delete
- `force` (boolean, optional): Force deletion even if entries exist. Default: `false`

**Response:**
```json
{
  "id": 1,
  "name": "Blog Posts",
  "handle": "blogPosts",
  "type": "channel",
  "impact": {
    "hasContent": false,
    "entryCount": 0,
    "draftCount": 0,
    "revisionCount": 0,
    "entryTypeCount": 2,
    "entryTypes": [
      {
        "id": 1,
        "name": "Post",
        "handle": "post"
      }
    ]
  }
}
```

## Error Handling

If a request encounters an error, the API returns an appropriate HTTP status code with error details:

- `400 Bad Request`: Invalid parameters or missing required fields
- `404 Not Found`: Section or referenced resource not found
- `422 Unprocessable Entity`: Validation failed (e.g., duplicate handle)
- `500 Internal Server Error`: Server-side error

Error responses include an error message explaining the issue.

## Usage Notes

- **Site Settings**: If not provided, sections are automatically enabled for all sites with default URI formats based on section type
- **Entry Types**: Must be created separately using the Craft CMS entry type tools before being assigned to sections
- **Type Changes**: Some section type conversions are restricted when entries exist (e.g., cannot convert Structure → Channel with existing hierarchical data)
- **Force Delete**: Always require user approval before setting `force=true` on delete operations
- **Control Panel Links**: After creating or updating sections, users can review changes in the Craft control panel using the `editUrl` provided in responses
