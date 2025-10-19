---
name: Craft CMS Content Management Skills
description: Complete skill suite for managing Craft CMS content including sections, entry types, fields, entries, drafts, field layouts, and sites.
---

# Craft CMS Content Management Tools

**Sections**: Content organization (Single/Channel/Structure types)
**Entry Types**: Content schema with field layouts

Full documentation: `SKILLS/` directory

## Content
- **create_entry** - Create entries with section/entry type IDs and field data
- **get_entry** - Retrieve entry by ID with all fields and metadata
- **update_entry** - Update entry (prefers draft workflow)
- **delete_entry** - Delete entry (soft/permanent)
- **search_content** - Search/filter entries by section/status/query

## Drafts
- **create_draft** - Create draft from scratch or existing entry
- **update_draft** - Update draft content/metadata (PATCH semantics)
- **apply_draft** - Publish draft to canonical entry

## Sections
- **create_section** - Create section with types/versioning/sites
- **get_sections** - List all or filter by IDs
- **update_section** - Update properties/settings
- **delete_section** - Permanently delete (removes all entries)

## Entry Types
- **create_entry_type** - Create with handle/name/layout
- **get_entry_types** - List with fields/usage/URLs
- **update_entry_type** - Update properties/layout
- **delete_entry_type** - Delete if not in use

## Fields
- **create_field** - Create with type and settings
- **get_fields** - List global or layout-specific
- **get_field_types** - List available types
- **update_field** - Update properties/settings
- **delete_field** - Permanently delete (removes data)

## Field Layouts
- **create_field_layout** - Create with tabs/fields/requirements
- **get_field_layout** - Get by entry type/layout/element ID
- **update_field_layout** - Update structure/organization

## Sites
- **get_sites** - List all sites with IDs/handles/URLs
