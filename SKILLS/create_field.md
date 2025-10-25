# create_field

Create new custom fields with specified field types and settings.

## Route

`POST /api/fields`

## Description

Creates a new custom field in Craft CMS. Fields define content structure and data types for entries. Use `get_field_types` to discover available field types before creating.

## Parameters

### Required Parameters

- **name** (string): Display name for the field
- **type** (string): Field type class (e.g., `craft\fields\PlainText`). Use `get_field_types` to discover available types.

### Optional Parameters

- **handle** (string, optional): Machine-readable name. Auto-generated if not provided.
- **instructions** (string, optional): Instructions for content editors
- **searchable** (boolean, optional): Whether the field values should be searchable. Default: `true`
- **translationMethod** (string, optional): How field content is translated: `none`, `site`, `language`, or `custom`. Default: `none`
- **settings** (object, optional): Field-type-specific settings (varies by field type)

## Return Value

Returns field ID, handle, type, and configuration details, plus a control panel URL to review and further configure the field.

## Example Usage

### Plain Text Field

```json
{
  "name": "Body Content",
  "handle": "bodyContent",
  "type": "craft\\fields\\PlainText",
  "instructions": "Enter the main body content",
  "searchable": true,
  "settings": {
    "columnType": "text",
    "placeholder": "Enter content here...",
    "charLimit": 500,
    "multiline": true
  }
}
```

### Matrix Field (Flexible Content Blocks)

Matrix fields allow flexible, repeatable content blocks with nested fields. Before creating a Matrix field, you must first create entry types that will serve as block types.

**Step 1: Create Entry Types for Block Types**

```json
POST /api/entry-types
{
  "name": "Text Block",
  "handle": "textBlock"
}

POST /api/entry-types
{
  "name": "Image Block",
  "handle": "imageBlock"
}
```

Both requests will return objects including a `uid` field - save these UIDs for the next step.

**Step 2: Create Matrix Field**

```json
POST /api/fields
{
  "name": "Content Blocks",
  "handle": "contentBlocks",
  "type": "craft\\fields\\Matrix",
  "instructions": "Add flexible content blocks",
  "searchable": true,
  "settings": {
    "minEntries": 1,
    "maxEntries": 10,
    "viewMode": "cards",
    "showCardsInGrid": false,
    "createButtonLabel": "Add Content Block",
    "entryTypes": [
      {"uid": "entry-type-uid-from-text-block"},
      {"uid": "entry-type-uid-from-image-block"}
    ]
  }
}
```

#### Matrix Field Settings

- **minEntries** (integer, optional): Minimum number of entries required
- **maxEntries** (integer, optional): Maximum number of entries allowed
- **viewMode** (string, optional): How blocks are displayed. Options:
  - `"cards"` - Card-based layout (default)
  - `"blocks"` - Compact block layout
  - `"index"` - Element index view
- **showCardsInGrid** (boolean, optional): Display cards in multi-column grid. Default: `false`
- **includeTableView** (boolean, optional): Include table view option in element indexes. Default: `false`
- **createButtonLabel** (string, optional): Custom label for "Add Entry" button
- **entryTypes** (array, required): Array of entry type configurations. Each must have:
  - **uid** (string, required): The UID of an existing entry type to use as a block type

### Other Common Field Types

#### URL Field

```json
{
  "name": "Website URL",
  "handle": "websiteUrl",
  "type": "craft\\fields\\Url",
  "instructions": "Enter a valid URL",
  "settings": {
    "placeholder": "https://example.com"
  }
}
```

#### Dropdown Field

```json
{
  "name": "Category",
  "handle": "category",
  "type": "craft\\fields\\Dropdown",
  "instructions": "Select a category",
  "settings": {
    "options": [
      {"label": "News", "value": "news", "default": false},
      {"label": "Blog", "value": "blog", "default": true},
      {"label": "Press", "value": "press", "default": false}
    ]
  }
}
```

#### Assets Field

```json
{
  "name": "Featured Image",
  "handle": "featuredImage",
  "type": "craft\\fields\\Assets",
  "instructions": "Upload a featured image",
  "settings": {
    "sources": ["volume:images"],
    "limit": 1,
    "allowedKinds": ["image"],
    "viewMode": "large"
  }
}
```

#### Entries Field (Relations)

```json
{
  "name": "Related Articles",
  "handle": "relatedArticles",
  "type": "craft\\fields\\Entries",
  "instructions": "Select related articles",
  "settings": {
    "sources": ["section:abc123-4567-89ab-cdef-123456789abc"],
    "limit": 5,
    "viewMode": "list",
    "selectionLabel": "Add an article"
  }
}
```

##### Entries Field Settings

- **sources** (array, required): Array of section UIDs that entries can be selected from. Format: `["section:UID"]`
  - Use section UIDs (not IDs) in the format `"section:{uid}"`
  - Get section UIDs from `get_sections` endpoint
  - Multiple sections can be specified: `["section:uid1", "section:uid2"]`
  - Empty array `[]` allows selection from all sections
- **limit** (integer, optional): Maximum number of entries that can be selected
- **viewMode** (string, optional): Display mode - `"list"` or `"cards"`
- **selectionLabel** (string, optional): Label for the selection button
- **localizeRelations** (boolean, optional): Whether relations are per-site. Default: `false`

## Notes

- Always use `get_field_types` to discover available field types and their class names
- Field settings vary significantly by field type - consult Craft CMS documentation for specific field type settings
- For Matrix fields, entry types must be created first using `create_entry_type`
- Matrix field entry types are referenced by their `uid` property
- **For Entries fields**: The `sources` array must contain section UIDs (not IDs) in the format `"section:{uid}"`. Use `get_sections` to retrieve section UIDs.
- After creation, fields must be added to field layouts to appear in entry forms (use `add_field_to_field_layout`)
- The response includes an `editUrl` pointing to the field settings in the Craft control panel for further configuration

## See Also

- `get_field_types` - Discover available field types
- `create_entry_type` - Create entry types for use in Matrix fields or sections
- `add_field_to_field_layout` - Add fields to entry type layouts
- `update_field` - Modify field settings after creation
- `delete_field` - Remove a field
