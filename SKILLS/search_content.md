# search_content

Search for content across Craft CMS with flexible filtering options.

## Description

Searches for entries in the Craft CMS system. Returns matching entries with their IDs, titles, and control panel edit URLs. Supports filtering by section, status, search query, and result limits.

## Parameters

### Optional Parameters

- **query** (string, optional): Search query text to match against entry content. If omitted, returns all entries (filtered by other parameters).
- **limit** (integer, optional): Maximum number of results to return. Default: 5.
- **status** (string, optional): Entry status filter. Options:
  - `live` (default): Published, enabled entries
  - `pending`: Scheduled for future publication
  - `expired`: Past expiration date
  - `disabled`: Manually disabled entries
- **sectionIds** (array of integers, optional): Filter results to specific sections. Only entries from these sections will be returned.

## Search Patterns

The tool supports various search combinations:

- **Search across all sections**: Provide only `query`
- **Search within specific sections**: Provide both `query` and `sectionIds`
- **Get all entries from sections**: Provide only `sectionIds` (no query)
- **Get all entries**: Provide neither parameter (returns first 5 live entries)

## Return Value

Returns an object containing:

- **_notes** (string): Descriptive message about the search results
- **results** (array): Array of matching entries, each containing:
  - **entryId** (integer): Entry ID
  - **title** (string): Entry title
  - **url** (string): Craft control panel edit URL

## Example Usage

### Search All Sections
```json
{
  "query": "coffee recipes",
  "limit": 10,
  "status": "live"
}
```

### Search Specific Sections
```json
{
  "query": "annual report",
  "sectionIds": [1, 3, 5],
  "limit": 20
}
```

### Get All Entries from Sections
```json
{
  "sectionIds": [2],
  "limit": 50
}
```

### Get All Live Entries
```json
{
  "limit": 100,
  "status": "live"
}
```

## Notes

- Default limit is 5 entries - increase for broader searches
- Use `get_sections` to discover valid section IDs
- Search query matches against entry content, not just titles
- Control panel URLs allow users to quickly navigate to entries for editing
- Section validation ensures provided section IDs exist
