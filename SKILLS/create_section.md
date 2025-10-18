# create_section

Create new sections with configurable types, entry types, and site settings.

## Description

Creates a new section in Craft CMS. Sections define the structural organization of content with three types:
- **Single**: One entry per section (e.g., homepage, about page)
- **Channel**: Multiple entries with flexible structure (e.g., news, blog posts)
- **Structure**: Hierarchical entries with parent-child relationships (e.g., pages with nested structure)

After creating the section, always link the user back to the section settings in the Craft control panel for further configuration.

## Parameters

### Required Parameters

- **name** (string): The display name for the section
- **type** (string): Section type - must be one of:
  - `single`: One entry per section
  - `channel`: Multiple entries
  - `structure`: Hierarchical entries
- **entryTypeIds** (array of integers): Entry type IDs to assign to this section. Use `create_entry_type` to create entry types first. Can be empty array (uncommon but possible).

### Optional Parameters

- **handle** (string, optional): Machine-readable name. Auto-generated from name if not provided.
- **enableVersioning** (boolean, optional): Enable entry versioning. Default: `true`
- **propagationMethod** (string, optional): How content propagates across sites. Options:
  - `all` (default): Propagate to all sites
  - `siteGroup`: Propagate within site group
  - `language`: Propagate to sites in same language
  - `custom`: Custom propagation
  - `none`: No propagation
- **maxLevels** (integer, optional): Maximum hierarchy levels for structure sections. `null` or `0` for unlimited. (Structure sections only)
- **defaultPlacement** (string, optional): Where new entries are placed: `beginning` or `end`. Default: `end`. (Structure sections only)
- **maxAuthors** (integer, optional): Maximum number of authors per entry. `null` for unlimited.
- **siteSettings** (array, optional): Site-specific settings. If not provided, section enabled for all sites with defaults. Each object contains:
  - `siteId` (integer, required): Site ID
  - `enabledByDefault` (boolean, optional): Enable entries by default
  - `hasUrls` (boolean, optional): Whether entries have URLs
  - `uriFormat` (string, optional): URI format pattern (e.g., `"blog/{slug}"`)
  - `template` (string, optional): Template path for rendering

## Return Value

Returns an object containing:

- **sectionId** (integer): The newly created section's ID
- **name** (string): Section name
- **handle** (string): Section handle
- **type** (string): Section type
- **propagationMethod** (string): Propagation method
- **maxLevels** (integer, optional): Maximum hierarchy levels (structure only)
- **maxAuthors** (integer, optional): Maximum authors
- **editUrl** (string): Craft control panel URL for section settings

## Example Usage

### Basic Channel Section
```json
{
  "name": "Blog Posts",
  "type": "channel",
  "entryTypeIds": [1, 2],
  "enableVersioning": true
}
```

### Structure Section with Settings
```json
{
  "name": "Pages",
  "type": "structure",
  "entryTypeIds": [3],
  "handle": "pages",
  "maxLevels": 3,
  "defaultPlacement": "end",
  "siteSettings": [
    {
      "siteId": 1,
      "enabledByDefault": true,
      "hasUrls": true,
      "uriFormat": "{slug}",
      "template": "_pages/entry"
    }
  ]
}
```

### Single Section
```json
{
  "name": "Homepage",
  "type": "single",
  "entryTypeIds": [5],
  "siteSettings": [
    {
      "siteId": 1,
      "hasUrls": true,
      "uriFormat": "",
      "template": "_pages/homepage"
    }
  ]
}
```

## Notes

- Entry types must be created before assigning to sections
- Site settings default to all sites if not provided
- Structure sections support hierarchy with `maxLevels` setting
- After creation, configure further in the Craft control panel
- Use `get_sites` to discover valid site IDs
