---
name: Craft CMS Section and Entry Type Management
description: Manage sections and entry types in Craft CMS. Sections define content structure organization, while entry types define content schemas with field layouts.
---

# Craft CMS Section and Entry Type Management

This skill provides HTTP endpoints to manage sections and entry types in Craft CMS.

**Sections** define how content is organized and structured. They support three types: Single (one entry per section), Channel (multiple entries with flexible structure), and Structure (hierarchical entries with parent-child relationships).

**Entry Types** define the content schema with field layouts and can exist independently (for Matrix fields) or be assigned to sections to control entry structure and behavior.

---

## Section Management

### Querying Current Sections

**For LLM Assistant**: When asked about current sections in this Craft instance, query the sections list endpoint first to get the actual state:

```bash
curl http://craft-mcp.dev.markhuot.com/api/sections
```

This will return the authoritative list of sections currently configured in the Craft CMS instance. Always perform this query to provide accurate information about existing sections rather than referencing outdated documentation.

### Base URL

All section endpoints are prefixed with the configured API prefix (default: `/api`).

### Section Endpoints

#### Create a Section

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

---

## Entry Type Management

Entry types define the content schema and field layouts for entries in Craft CMS. They can exist independently (useful for Matrix fields) or be assigned to sections to control entry structure and behavior.

### Querying Current Entry Types

**For LLM Assistant**: When asked about entry types in this Craft instance, query the entry types list endpoint first:

```bash
curl http://craft-mcp.dev.markhuot.com/api/entry-types
```

This returns the authoritative list of entry types currently configured, including their field layouts and usage information.

### Base URL

All entry type endpoints are prefixed with the configured API prefix (default: `/api`).

### Entry Type Endpoints

#### Create an Entry Type

Create a new entry type with configurable field layout, title field settings, and visual presentation options.

**Request:**
```
POST /api/entry-types
Content-Type: application/json

{
  "name": "Article",
  "handle": "article",
  "hasTitleField": true,
  "titleTranslationMethod": "site",
  "titleFormat": null,
  "icon": "newspaper",
  "color": "blue",
  "description": "Standard article entry type",
  "showSlugField": true,
  "showStatusField": true
}
```

**Parameters:**
- `name` (string, required): The display name for the entry type
- `handle` (string, optional): The entry type handle (machine-readable name). Auto-generated from name if not provided
- `hasTitleField` (boolean, optional): Whether entries of this type have title fields. Default: `true`
- `titleTranslationMethod` (string, optional): How titles are translated: `none`, `site`, `language`, or `custom`. Default: `site`
- `titleTranslationKeyFormat` (string, optional): Translation key format for custom title translation. Required when `titleTranslationMethod` is `custom`
- `titleFormat` (string, optional): Custom title format pattern (e.g., `"{name} - {dateCreated|date}"`) for controlling entry title display. Required when `hasTitleField` is `false`
- `icon` (string, optional): Icon identifier for the entry type (e.g., `newspaper`, `image`, `calendar`)
- `color` (string, optional): Color identifier for the entry type (e.g., `red`, `blue`, `green`, `orange`, `pink`, `purple`, `turquoise`, `yellow`)
- `description` (string, optional): A short string describing the purpose of the entry type
- `showSlugField` (boolean, optional): Whether entries of this type show the slug field in the admin UI. Default: `true`
- `showStatusField` (boolean, optional): Whether entries of this type show the status field in the admin UI. Default: `true`

**Response:**
```json
{
  "_notes": "The entry type was successfully created. You can further configure it in the Craft control panel.",
  "entryTypeId": 1,
  "name": "Article",
  "handle": "article",
  "description": "Standard article entry type",
  "hasTitleField": true,
  "titleTranslationMethod": "site",
  "titleTranslationKeyFormat": null,
  "titleFormat": null,
  "icon": "newspaper",
  "color": "blue",
  "showSlugField": true,
  "showStatusField": true,
  "fieldLayoutId": 1,
  "editUrl": "https://example.com/admin/settings/entry-types/1"
}
```

#### List Entry Types

Retrieve all entry types or filter by entry type IDs. Returns complete field layout information and usage statistics.

**Request:**
```
GET /api/entry-types?entryTypeIds=1,2,3
```

**Parameters:**
- `entryTypeIds` (array of integers, optional): List of entry type IDs to retrieve. If omitted, returns all entry types

**Response:**
```json
[
  {
    "id": 1,
    "name": "Article",
    "handle": "article",
    "description": "Standard article entry type",
    "hasTitleField": true,
    "titleTranslationMethod": "site",
    "titleFormat": null,
    "icon": "newspaper",
    "color": "blue",
    "showSlugField": true,
    "showStatusField": true,
    "fieldLayoutId": 1,
    "fieldLayout": {
      "id": 1,
      "tabs": [
        {
          "name": "Content",
          "fields": [
            {
              "id": 1,
              "name": "Body",
              "handle": "body",
              "type": "craft\\fields\\PlainText"
            }
          ]
        }
      ]
    },
    "usedBy": {
      "sections": [
        {
          "id": 1,
          "name": "Blog",
          "handle": "blog",
          "type": "channel"
        }
      ],
      "matrixFields": []
    },
    "editUrl": "https://example.com/admin/settings/entry-types/1"
  }
]
```

#### Update an Entry Type

Update an existing entry type's properties, field layout assignment, or visual presentation.

**Request:**
```
PUT /api/entry-types/1
Content-Type: application/json

{
  "name": "Blog Article",
  "description": "Updated article entry type",
  "icon": "document-text",
  "color": "green",
  "fieldLayoutId": 2
}
```

**Parameters:**
- `name` (string, optional): The display name for the entry type
- `handle` (string, optional): The entry type handle (machine-readable name)
- `titleTranslationMethod` (string, optional): How titles are translated: `none`, `site`, `language`, or `custom`
- `titleTranslationKeyFormat` (string, optional): Translation key format for custom title translation
- `titleFormat` (string, optional): Custom title format pattern
- `icon` (string, optional): Icon identifier for the entry type
- `color` (string, optional): Color identifier for the entry type
- `description` (string, optional): A short string describing the purpose of the entry type
- `showSlugField` (boolean, optional): Whether entries show the slug field in the admin UI
- `showStatusField` (boolean, optional): Whether entries show the status field in the admin UI
- `fieldLayoutId` (integer, optional): The ID of the field layout to assign to this entry type

**Response:**
```json
{
  "id": 1,
  "name": "Blog Article",
  "handle": "article",
  "description": "Updated article entry type",
  "hasTitleField": true,
  "titleTranslationMethod": "site",
  "titleFormat": null,
  "icon": "document-text",
  "color": "green",
  "showSlugField": true,
  "showStatusField": true,
  "fieldLayoutId": 2,
  "editUrl": "https://example.com/admin/settings/entry-types/1"
}
```

#### Delete an Entry Type

Delete an entry type from Craft CMS. This will remove the entry type and its associated field layout.

**Request:**
```
DELETE /api/entry-types/1
Content-Type: application/json

{
  "force": false
}
```

**Parameters:**
- `force` (boolean, optional): Force deletion even if entries exist. Default: `false`. **Warning**: Using `force=true` will cause data loss for existing entries. Always require user approval before forcing deletion.

**Response:**
```json
{
  "_notes": "Entry type 'Article' was successfully deleted. This removed 15 associated items from the system.",
  "deleted": true,
  "entryType": {
    "id": 1,
    "name": "Article",
    "handle": "article",
    "fieldLayoutId": 1
  },
  "usageStats": {
    "entries": 10,
    "drafts": 3,
    "revisions": 2,
    "total": 15
  },
  "forced": true
}
```

---

## Error Handling

If a request encounters an error, the API returns an appropriate HTTP status code with error details:

- `400 Bad Request`: Invalid parameters or missing required fields
- `404 Not Found`: Section or referenced resource not found
- `422 Unprocessable Entity`: Validation failed (e.g., duplicate handle)
- `500 Internal Server Error`: Server-side error

Error responses include an error message explaining the issue.

## Usage Notes

### Section Management
- **Site Settings**: If not provided, sections are automatically enabled for all sites with default URI formats based on section type
- **Entry Types**: Must be created separately using the entry type endpoints before being assigned to sections
- **Type Changes**: Some section type conversions are restricted when entries exist (e.g., cannot convert Structure → Channel with existing hierarchical data)
- **Force Delete**: Always require user approval before setting `force=true` on delete operations
- **Control Panel Links**: After creating or updating sections, users can review changes in the Craft control panel using the `editUrl` provided in responses

### Entry Type Management
- **Field Layouts**: Entry types with `hasTitleField=true` automatically include a title field in their layout. Entry types with `hasTitleField=false` require a `titleFormat` to define how titles are generated
- **Title Format**: When `hasTitleField` is `false`, use `titleFormat` with Twig syntax like `"{fieldHandle} - {dateCreated|date}"` to define automatic title generation
- **Standalone Usage**: Entry types can exist independently without being assigned to sections (commonly used for Matrix field block types)
- **Usage Detection**: The list endpoint shows which sections and Matrix fields reference each entry type via the `usedBy` property
- **Force Delete**: Deleting entry types with existing entries requires `force=true` and causes permanent data loss. Always get user approval before forcing deletion
- **Control Panel Links**: After creating or updating entry types, users can review changes in the Craft control panel using the `editUrl` provided in responses
