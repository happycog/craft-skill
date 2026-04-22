# list_templates

List all site templates by filename.

## Tool

`list_templates` (MCP tool, also callable via CLI: `agent-craft templates/list`)

## Description

Returns every file in Craft's configured site templates directory. Filenames are returned relative to the templates root and use forward slashes for nested paths.

## Parameters

This tool has no parameters.

## Return Value

Returns an array of template filenames such as `index.twig` or `_partials/card.twig`.

## Example Usage

```json
{}
```

## Notes

- Uses Craft's configured `@templates` path
- Returns nested templates relative to the templates root
- Output is sorted by filename
