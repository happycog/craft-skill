# get_template

Retrieve the contents of a site template by filename.

## Tool

`get_template` (MCP tool, also callable via CLI: `agent-craft templates/get`)

## Description

Reads a template file from Craft's configured site templates directory and returns its contents.

## Parameters

### Required Parameters

- **filename** (string): Template path relative to the templates directory, such as `index.twig` or `_partials/card.twig`.

## Return Value

Returns an object containing:

- **filename** (string): The requested template filename
- **contents** (string): The full template contents

## Example Usage

```json
{
  "filename": "_partials/card.twig"
}
```

## Notes

- Template paths must stay within Craft's templates directory
- Throws an error if the file does not exist
- Returns the raw template source without rendering it
