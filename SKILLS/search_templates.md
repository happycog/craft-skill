# search_templates

Search site template contents for a plain-text needle string.

## Tool

`search_templates` (MCP tool, also callable via CLI: `agent-craft templates/search`)

## Description

Scans every file in Craft's configured site templates directory and returns matching lines for a case-sensitive substring search.

## Parameters

### Required Parameters

- **needle** (string): Plain-text string to search for within template contents.

## Return Value

Returns an object containing:

- **_notes** (string): Summary of the search result count
- **results** (array): Matching lines, each containing:
  - **filename** (string): Template filename relative to the templates directory
  - **lineNumber** (integer): 1-based line number of the match
  - **line** (string): Full line text containing the match

## Example Usage

```json
{
  "needle": "entry.title"
}
```

## Notes

- Matching is case-sensitive
- Searches all files in the configured templates directory
- Returns one result per matching line
