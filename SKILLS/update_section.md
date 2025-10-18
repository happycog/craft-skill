# update_section

Update section properties including name, type, and site configurations.

## Description

Updates an existing section in Craft CMS. Allows modification of section properties while preserving existing entry data where possible. Section type changes have restrictions - Single ↔ Channel is possible, but Structure changes require careful consideration due to hierarchical data.

After updating the section, always link the user back to the section settings in the Craft control panel.

## Parameters

### Required Parameters

- **sectionId** (integer): The ID of the section to update

### Optional Parameters

All parameters are optional - only provided values will be updated:

- **name** (string, optional): The display name for the section
- **handle** (string, optional): Machine-readable name
- **type** (string, optional): Section type (`single`, `channel`, or `structure`). Type changes have restrictions based on existing data.
- **entryTypeIds** (array of integers, optional): Entry type IDs to assign. Replaces existing associations.
- **enableVersioning** (boolean, optional): Enable/disable entry versioning
- **propagationMethod** (string, optional): How content propagates across sites (`all`, `siteGroup`, `language`, `custom`, `none`)
- **maxLevels** (integer, optional): Maximum hierarchy levels for structure sections. `null` or `0` for unlimited.
- **defaultPlacement** (string, optional): Where new entries are placed (`beginning` or `end`) for structure sections
- **maxAuthors** (integer, optional): Maximum authors per entry. `null` for unlimited.
- **siteSettingsData** (array, optional): Site-specific settings. Each object contains:
  - `siteId` (integer, required): Site ID
  - `enabledByDefault` (boolean, optional): Enable entries by default
  - `hasUrls` (boolean, optional): Whether entries have URLs
  - `uriFormat` (string, optional): URI format pattern
  - `template` (string, optional): Template path

## Return Value

Returns an object containing:

- **sectionId** (integer): The updated section's ID
- **name** (string): Section name
- **handle** (string): Section handle
- **type** (string): Section type
- **propagationMethod** (string): Propagation method
- **maxLevels** (integer, optional): Maximum hierarchy levels
- **maxAuthors** (integer, optional): Maximum authors
- **editUrl** (string): Craft control panel URL for section settings

## Example Usage

### Update Section Name and Versioning
```json
{
  "sectionId": 1,
  "name": "News Articles",
  "enableVersioning": false
}
```

### Update Entry Type Associations
```json
{
  "sectionId": 1,
  "entryTypeIds": [1, 2, 3]
}
```

### Update Site Settings
```json
{
  "sectionId": 2,
  "siteSettingsData": [
    {
      "siteId": 1,
      "hasUrls": true,
      "uriFormat": "news/{slug}",
      "template": "_news/article"
    }
  ]
}
```

### Change Section Type
```json
{
  "sectionId": 1,
  "type": "channel"
}
```

## Notes

- Only provided parameters are updated - omitted values remain unchanged
- Section type changes have restrictions based on existing entries
- Entry type associations replace existing ones when provided
- Site settings are merged with existing settings
- After update, users can review changes in the Craft control panel
- Validates that referenced entry types exist before updating
