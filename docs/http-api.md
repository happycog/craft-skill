# HTTP API Documentation

This plugin provides a RESTful HTTP API for all MCP tools, making Craft CMS content management accessible via standard HTTP requests.

## Base URL

All API endpoints are prefixed with `/api` by default (configurable via plugin settings):

```
http://your-craft-site.com/api/...
```

## Authentication

Currently, the API allows anonymous access. In production, you should implement authentication and authorization.

## Response Format

All endpoints return JSON responses with the tool's return value:

```json
{
  "sectionId": 1,
  "name": "Blog",
  "handle": "blog",
  "type": "channel",
  "editUrl": "https://..."
}
```

Validation errors return a 400 status code with error details:

```json
{
  "error": "Validation error message..."
}
```

## Endpoints

### Sections

#### Create Section
```http
POST /api/sections
Content-Type: application/json

{
  "name": "Blog",
  "type": "channel",
  "entryTypeIds": [1, 2],
  "handle": "blog",
  "enableVersioning": true,
  "propagationMethod": "all"
}
```

#### List Sections
```http
GET /api/sections?includeArchived=false
```

#### Update Section
```http
PUT /api/sections/1
Content-Type: application/json

{
  "name": "Updated Blog",
  "handle": "updated-blog"
}
```

#### Delete Section
```http
DELETE /api/sections/1
```

### Entry Types

#### Create Entry Type
```http
POST /api/entry-types
Content-Type: application/json

{
  "name": "Article",
  "handle": "article",
  "hasTitleField": true
}
```

#### List Entry Types
```http
GET /api/entry-types?sectionId=1
```

#### Update Entry Type
```http
PUT /api/entry-types/1
Content-Type: application/json

{
  "name": "Updated Article",
  "hasTitleField": false
}
```

#### Delete Entry Type
```http
DELETE /api/entry-types/1
```

### Fields

#### Create Field
```http
POST /api/fields
Content-Type: application/json

{
  "name": "Body Content",
  "handle": "bodyContent",
  "type": "craft\\fields\\PlainText",
  "settings": {
    "columnType": "text"
  }
}
```

#### List Fields
```http
GET /api/fields
```

#### Get Field Types
```http
GET /api/fields/types
```

#### Update Field
```http
PUT /api/fields/1
Content-Type: application/json

{
  "name": "Updated Body Content",
  "settings": {
    "columnType": "mediumtext"
  }
}
```

#### Delete Field
```http
DELETE /api/fields/1
```

### Entries

#### Create Entry
```http
POST /api/entries
Content-Type: application/json

{
  "sectionId": 1,
  "entryTypeId": 1,
  "title": "My New Blog Post",
  "fields": {
    "bodyContent": "This is the content..."
  },
  "siteId": 1,
  "enabled": true
}
```

#### Get Entry
```http
GET /api/entries/1?siteId=1
```

#### Search Entries
```http
GET /api/entries/search?section=blog&limit=10&search=keyword
```

#### Update Entry
```http
PUT /api/entries/1
Content-Type: application/json

{
  "title": "Updated Title",
  "fields": {
    "bodyContent": "Updated content..."
  }
}
```

#### Delete Entry
```http
DELETE /api/entries/1
```

### Drafts

#### Create Draft
```http
POST /api/drafts
Content-Type: application/json

{
  "entryId": 1,
  "draftName": "My Draft",
  "draftNotes": "Working on improvements",
  "fields": {
    "bodyContent": "Draft content..."
  }
}
```

#### Update Draft
```http
PUT /api/drafts/2
Content-Type: application/json

{
  "draftName": "Updated Draft",
  "fields": {
    "bodyContent": "More changes..."
  }
}
```

#### Apply Draft
```http
POST /api/drafts/2/apply
```

### Field Layouts

#### Create Field Layout
```http
POST /api/field-layouts
Content-Type: application/json

{
  "type": "craft\\elements\\Entry",
  "elementId": 1,
  "tabs": [
    {
      "name": "Content",
      "fields": [
        {
          "fieldId": 1,
          "required": true
        }
      ]
    }
  ]
}
```

#### Get Field Layout
```http
GET /api/field-layouts?entryTypeId=1
```

#### Update Field Layout
```http
PUT /api/field-layouts/1
Content-Type: application/json

{
  "tabs": [
    {
      "name": "Content",
      "fields": [
        {
          "fieldId": 1,
          "required": false
        },
        {
          "fieldId": 2,
          "required": true
        }
      ]
    }
  ]
}
```

### Sites

#### List Sites
```http
GET /api/sites
```

## Parameter Mapping

### URL Parameters (for GET/DELETE)
Query parameters are automatically mapped to tool method parameters.

### Body Parameters (for POST/PUT)
JSON body parameters are automatically mapped to tool method parameters.

### Path Parameters
Route parameters (like `<id>`) are mapped to specific tool parameters:
- Sections: `sectionId`
- Entry Types: `entryTypeId`
- Fields: `fieldId`
- Entries: `entryId`
- Drafts: `draftId`
- Field Layouts: `fieldLayoutId`

## Validation

The API uses [Valinor](https://github.com/CuyZ/Valinor) for automatic validation and type mapping. All tool parameters are validated according to their type hints and the request will fail with a 400 error if validation fails.

## Examples

### Create a complete blog setup

```bash
# 1. Create an entry type
curl -X POST http://craft-site.com/api/entry-types \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Article",
    "handle": "article",
    "hasTitleField": true
  }'

# Response: {"entryTypeId": 1, "name": "Article", ...}

# 2. Create a section with the entry type
curl -X POST http://craft-site.com/api/sections \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Blog",
    "type": "channel",
    "handle": "blog",
    "entryTypeIds": [1]
  }'

# Response: {"sectionId": 1, "name": "Blog", ...}

# 3. Create a field
curl -X POST http://craft-site.com/api/fields \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Body",
    "handle": "body",
    "type": "craft\\fields\\PlainText"
  }'

# Response: {"fieldId": 1, "name": "Body", ...}

# 4. Create an entry
curl -X POST http://craft-site.com/api/entries \
  -H "Content-Type: application/json" \
  -d '{
    "sectionId": 1,
    "entryTypeId": 1,
    "title": "My First Post",
    "fields": {
      "body": "This is my first blog post!"
    }
  }'

# Response: {"entryId": 1, "title": "My First Post", ...}
```

### Create a draft and apply it

```bash
# 1. Create a draft of an existing entry
curl -X POST http://craft-site.com/api/drafts \
  -H "Content-Type: application/json" \
  -d '{
    "entryId": 1,
    "draftName": "Update body content",
    "fields": {
      "body": "Updated content for the post"
    }
  }'

# Response: {"draftId": 2, "entryId": 1, ...}

# 2. Apply the draft to publish changes
curl -X POST http://craft-site.com/api/drafts/2/apply
```

## Configuration

### Custom API Prefix

You can configure the API prefix in your plugin settings:

```php
// config/mcp.php
return [
    'apiPrefix' => 'v1/api', // Default is 'api'
];
```

This would change all routes to `/v1/api/sections`, `/v1/api/entries`, etc.
