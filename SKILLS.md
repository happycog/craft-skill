---
name: Craft CMS Content Management MCP Tools
description: Complete MCP tool suite for managing all aspects of Craft CMS content including sections, entry types, fields, entries, drafts, field layouts, and sites.
---

# Craft CMS Content Management MCP Tools

This skill provides comprehensive MCP tools for managing Craft CMS content and configuration through the Model Context Protocol.

**Sections** define how content is organized and structured. They support three types: Single (one entry per section), Channel (multiple entries with flexible structure), and Structure (hierarchical entries with parent-child relationships).

**Entry Types** define the content schema with field layouts and can exist independently (for Matrix fields) or be assigned to sections to control entry structure and behavior.

## Tool Documentation

Each tool has detailed documentation in the `SKILLS/` directory. Click the tool name to view complete parameter details and examples.

---

## Content Management Tools

### [create_entry](SKILLS/create_entry.md)
Create new entries in Craft CMS. Requires section ID and entry type ID, accepts custom field data and native attributes. Returns entry ID and control panel URL for review.

### [get_entry](SKILLS/get_entry.md)
Retrieve complete entry details by ID. Returns all entry data including custom fields, attributes, and metadata.

### [update_entry](SKILLS/update_entry.md)
Update existing entry content and attributes. Prefers draft workflow for staged changes. Returns updated entry information and control panel URL.

### [delete_entry](SKILLS/delete_entry.md)
Delete entries with soft delete (default) or permanent deletion options. Returns deleted entry information and optional restore URL.

### [search_content](SKILLS/search_content.md)
Search for content across Craft CMS. Filter by section, status, or search query. Returns matching entries with IDs, titles, and control panel URLs.

---

## Draft Management Tools

### [create_draft](SKILLS/create_draft.md)
Create drafts either from scratch or from existing published entries. Supports draft names, notes, and provisional drafts for auto-save functionality.

### [update_draft](SKILLS/update_draft.md)
Update draft content and metadata using PATCH semantics. Supports updating fields, draft names, and draft notes without affecting other data.

### [apply_draft](SKILLS/apply_draft.md)
Apply draft changes to the canonical entry, making content live. Removes the draft after successful application.

---

## Section Management Tools

### [create_section](SKILLS/create_section.md)
Create new sections with configurable types (single, channel, structure), entry types, versioning, propagation methods, and site-specific settings.

### [get_sections](SKILLS/get_sections.md)
List all sections or filter by section IDs. Returns section details including entry types, handles, names, and types.

### [update_section](SKILLS/update_section.md)
Update section properties including name, type, entry types, versioning settings, and site configurations.

### [delete_section](SKILLS/delete_section.md)
Delete sections permanently. Warning: This removes all associated entries and cannot be undone.

---

## Entry Type Management Tools

### [create_entry_type](SKILLS/create_entry_type.md)
Create new entry types with custom handles, names, titles, and field layouts. Can be standalone or assigned to sections.

### [get_entry_types](SKILLS/get_entry_types.md)
List all entry types with complete field information, usage stats (sections and Matrix fields), and edit URLs.

### [update_entry_type](SKILLS/update_entry_type.md)
Update entry type properties including name, handle, title format, and associated field layout.

### [delete_entry_type](SKILLS/delete_entry_type.md)
Delete entry types. Validates that entry type is not in use by sections or Matrix fields before deletion.

---

## Field Management Tools

### [create_field](SKILLS/create_field.md)
Create new custom fields with specified field types and settings. Returns field ID, handle, and configuration details.

### [get_fields](SKILLS/get_fields.md)
List all global fields or fields for a specific field layout. Returns field handles, types, labels, and configuration.

### [get_field_types](SKILLS/get_field_types.md)
Discover available field types in the Craft installation including plugin-provided types. Returns class names, display names, and descriptions.

### [update_field](SKILLS/update_field.md)
Update existing field properties including name, handle, instructions, and field-type-specific settings.

### [delete_field](SKILLS/delete_field.md)
Delete custom fields permanently. Warning: Removes field data from all entries using this field.

---

## Field Layout Management Tools

### [create_field_layout](SKILLS/create_field_layout.md)
Create field layouts with organized tabs and fields. Define field requirements and custom instructions per layout.

### [get_field_layout](SKILLS/get_field_layout.md)
Retrieve field layout details including tabs, fields, and organization. Query by entry type ID, field layout ID, element type, or element ID.

### [update_field_layout](SKILLS/update_field_layout.md)
Update field layout structure including tab organization, field assignments, and field requirements.

---

## Site Management Tools

### [get_sites](SKILLS/get_sites.md)
List all available sites in multi-site installations. Returns site IDs, names, handles, URLs, primary site indicator, and language codes.
