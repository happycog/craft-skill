---
name: Craft CMS Content Management Skills
description: Complete skill suite for managing Craft CMS content including sections, entry types, fields, entries, drafts, field layouts, and sites.
---

# Craft CMS Content Management Tools

**Sections**: Content organization (Single/Channel/Structure types)
**Entry Types**: Content schema with field layouts

Full documentation: `SKILLS/` directory

## Important: Use the API, Not YAML Files

**CRITICAL**: Always use this HTTP API to manage Craft CMS content. Never directly modify YAML configuration files in the `config/project/` directory. The API ensures proper validation, maintains data integrity, and handles all necessary relationships automatically. Direct YAML edits can corrupt your Craft installation.

## Base URL Configuration

All API routes require a base URL and API prefix. The standard Craft CMS configuration uses the `PRIMARY_SITE_URL` environment variable and a configurable API prefix:

- **Environment Variable**: Check for `PRIMARY_SITE_URL` in ENV or `.env` file
- **If Not Set**: Ask the user for the base URL to use
- **API Prefix**: Configurable prefix that defaults to `/api`
  - **Check Order**:
    1. First check `config/skills.php` for `apiPrefix` in the PHP array
    2. If not found, try the default `/api`
    3. If requests fail, ask the user for the configured API prefix
- **Route Format**: `{PRIMARY_SITE_URL}/{apiPrefix}/{endpoint}`
- **Default Example**: `https://craft-site.com/api/sections`
- **Custom Prefix Example**: `https://craft-site.com/custom-api/sections`

## Request/Response Format

All API endpoints:
- **Return JSON**: All responses are in JSON format with structured data
- **Accept Header**: Include `Accept: application/json` header in requests to ensure errors are also formatted as JSON for better error handling and debugging
- **Content-Type**: Use `Content-Type: application/json` for POST/PUT requests with JSON body data

## Content
- **create_entry** - `POST /api/entries` - Create entries with section/entry type IDs and field data
- **get_entry** - `GET /api/entries/<id>` - Retrieve entry by ID with all fields and metadata
- **update_entry** - `PUT /api/entries/<id>` - Update entry (prefers draft workflow)
- **delete_entry** - `DELETE /api/entries/<id>` - Delete entry (soft/permanent)
- **search_content** - `GET /api/entries/search` - Search/filter entries by section/status/query

## Drafts
- **create_draft** - `POST /api/drafts` - Create draft from scratch or existing entry
- **update_draft** - `PUT /api/drafts/<id>` - Update draft content/metadata (PATCH semantics)
- **apply_draft** - `POST /api/drafts/<id>/apply` - Publish draft to canonical entry

## Sections
- **create_section** - `POST /api/sections` - Create section with types/versioning/sites
- **get_sections** - `GET /api/sections` - List all or filter by IDs
- **update_section** - `PUT /api/sections/<id>` - Update properties/settings
- **delete_section** - `DELETE /api/sections/<id>` - Permanently delete (removes all entries)

## Entry Types
- **create_entry_type** - `POST /api/entry-types` - Create with handle/name/layout
- **get_entry_types** - `GET /api/entry-types` - List with fields/usage/URLs
- **update_entry_type** - `PUT /api/entry-types/<id>` - Update properties/layout
- **delete_entry_type** - `DELETE /api/entry-types/<id>` - Delete if not in use

## Fields
- **create_field** - `POST /api/fields` - Create with type and settings
- **get_fields** - `GET /api/fields` - List global or layout-specific
- **get_field_types** - `GET /api/fields/types` - List available types
- **update_field** - `PUT /api/fields/<id>` - Update properties/settings
- **delete_field** - `DELETE /api/fields/<id>` - Permanently delete (removes data)

## Field Layouts
- **create_field_layout** - `POST /api/field-layouts` - Create empty field layout for entry types
- **get_field_layout** - `GET /api/field-layouts` - Get field layout structure by entry type/layout/element ID
- **add_tab_to_field_layout** - `POST /api/field-layouts/<id>/tabs` - Add tab to field layout with flexible positioning (prepend/append/before/after)
- **add_field_to_field_layout** - `POST /api/field-layouts/<id>/fields` - Add custom field to tab with positioning, width, required, and display options
- **add_ui_element_to_field_layout** - `POST /api/field-layouts/<id>/ui-elements` - Add UI elements (heading, tip, horizontal rule, markdown, template) to layouts
- **move_element_in_field_layout** - `PUT /api/field-layouts/<id>/elements` - Move fields/UI elements within or between tabs with precise positioning
- **remove_element_from_field_layout** - `DELETE /api/field-layouts/<id>/elements` - Remove fields or UI elements from field layout

## Sites
- **create_site** - `POST /api/sites` - Create site with name/URL/language/handle
- **get_sites** - `GET /api/sites` - List all sites with IDs/handles/URLs
- **update_site** - `PUT /api/sites/<id>` - Update site properties/settings
