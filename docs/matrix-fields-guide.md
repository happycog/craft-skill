# Matrix Field Setup Guide

This guide demonstrates how to create Matrix fields with nested content blocks in Craft CMS using the API.

## Overview

Matrix fields allow flexible, repeatable content blocks with nested fields. Each block type is defined as an entry type with its own field layout.

## Complete Workflow

### Step 1: Create Entry Types (Block Types)

First, create entry types that will serve as Matrix block types:

```bash
# Create a text block type
curl -X POST http://craft-site.com/api/entry-types \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Text Block",
    "handle": "textBlock",
    "description": "A simple text content block"
  }'

# Response includes uid:
# {
#   "entryTypeId": 1,
#   "uid": "abc123-def456-ghi789",
#   "name": "Text Block",
#   ...
# }

# Create an image block type
curl -X POST http://craft-site.com/api/entry-types \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Image Block",
    "handle": "imageBlock",
    "description": "An image with optional caption"
  }'

# Response includes uid:
# {
#   "entryTypeId": 2,
#   "uid": "xyz789-abc123-def456",
#   ...
# }
```

**Important**: Save the `uid` values from each response - you'll need them for the Matrix field configuration.

### Step 2: Create Fields for Block Types (Optional)

If you want custom fields in your blocks, create them:

```bash
# Create a text content field
curl -X POST http://craft-site.com/api/fields \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Text Content",
    "handle": "textContent",
    "type": "craft\\fields\\PlainText",
    "settings": {
      "multiline": true,
      "charLimit": 1000,
      "placeholder": "Enter your text content here..."
    }
  }'

# Create a caption field
curl -X POST http://craft-site.com/api/fields \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Caption",
    "handle": "caption",
    "type": "craft\\fields\\PlainText",
    "settings": {
      "multiline": false,
      "charLimit": 200,
      "placeholder": "Enter image caption..."
    }
  }'
```

### Step 3: Add Fields to Block Layouts (Optional)

Add your custom fields to the entry type field layouts:

```bash
# Add text field to text block layout
curl -X POST http://craft-site.com/api/field-layouts/{fieldLayoutId}/fields \
  -H "Content-Type: application/json" \
  -d '{
    "fieldId": 1,
    "tabName": "Content",
    "position": {"type": "append"},
    "required": true
  }'

# Add caption field to image block layout
curl -X POST http://craft-site.com/api/field-layouts/{fieldLayoutId}/fields \
  -H "Content-Type: application/json" \
  -d '{
    "fieldId": 2,
    "tabName": "Content",
    "position": {"type": "append"},
    "required": false
  }'
```

### Step 4: Create Matrix Field

Finally, create the Matrix field with the entry types as block types:

```bash
curl -X POST http://craft-site.com/api/fields \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Flexible Content",
    "handle": "flexibleContent",
    "type": "craft\\fields\\Matrix",
    "instructions": "Add flexible content blocks of text and images",
    "settings": {
      "minEntries": 1,
      "maxEntries": 20,
      "viewMode": "cards",
      "showCardsInGrid": true,
      "createButtonLabel": "Add Content Block",
      "entryTypes": [
        {"uid": "abc123-def456-ghi789"},
        {"uid": "xyz789-abc123-def456"}
      ]
    }
  }'
```

## Matrix Field Settings Reference

### Required Settings

- **entryTypes** (array): Array of entry type configurations, each with a `uid` field

### Optional Settings

- **minEntries** (integer): Minimum number of entries required (default: none)
- **maxEntries** (integer): Maximum number of entries allowed (default: none)
- **viewMode** (string): How blocks are displayed
  - `"cards"` - Card-based layout (default)
  - `"blocks"` - Compact block layout
  - `"index"` - Element index view
- **showCardsInGrid** (boolean): Display cards in multi-column grid (default: false)
- **includeTableView** (boolean): Include table view option in element indexes (default: false)
- **createButtonLabel** (string): Custom label for "Add Entry" button
- **pageSize** (integer): Number of entries to display per page in element indexes
- **defaultTableColumns** (array): Default columns to show in table view

## Common Patterns

### Simple Content Builder

Two block types: text and quote

```json
{
  "name": "Page Builder",
  "type": "craft\\fields\\Matrix",
  "settings": {
    "viewMode": "blocks",
    "entryTypes": [
      {"uid": "text-block-uid"},
      {"uid": "quote-block-uid"}
    ]
  }
}
```

### Limited Flexible Content

Restrict number of blocks for focused content:

```json
{
  "name": "Hero Sections",
  "type": "craft\\fields\\Matrix",
  "settings": {
    "minEntries": 1,
    "maxEntries": 3,
    "viewMode": "cards",
    "showCardsInGrid": true,
    "entryTypes": [
      {"uid": "hero-banner-uid"},
      {"uid": "feature-grid-uid"}
    ]
  }
}
```

### Complex Page Builder

Many block types with custom button label:

```json
{
  "name": "Content Sections",
  "type": "craft\\fields\\Matrix",
  "settings": {
    "viewMode": "cards",
    "createButtonLabel": "Add Section",
    "entryTypes": [
      {"uid": "text-uid"},
      {"uid": "image-uid"},
      {"uid": "gallery-uid"},
      {"uid": "video-uid"},
      {"uid": "quote-uid"},
      {"uid": "callout-uid"}
    ]
  }
}
```

## Testing

See `tests/MatrixFieldIntegrationTest.php` for a complete PHP example demonstrating the full workflow programmatically.

## Notes

- Entry types used as Matrix block types can be reused across multiple Matrix fields
- Each block type maintains its own field layout independently
- Matrix fields support unlimited nesting depth (Matrix within Matrix)
- Block types appear in the order they're listed in the `entryTypes` array
- The `uid` property is stable across environments, making it ideal for deployment/migration scenarios

## See Also

- [create_field](./create_field.md) - Full field creation documentation
- [create_entry_type](./create_entry_type.md) - Entry type creation documentation
- [add_field_to_field_layout](./add_field_to_field_layout.md) - Adding fields to layouts
- [get_field_types](./get_field_types.md) - Discover available field types
