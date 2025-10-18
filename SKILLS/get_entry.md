# get_entry

Retrieve complete entry details by ID.

## Description

Gets detailed information about a specific entry in Craft CMS, including all custom fields, native attributes, and metadata.

## Parameters

### Required Parameters

- **entryId** (integer): The ID of the entry to retrieve.

## Return Value

Returns a complete array representation of the entry containing all fields and attributes, including:

- Entry ID and basic info (title, slug, status)
- Section and entry type information
- Custom field values
- Publication dates
- Author information
- Site information
- All other entry metadata

## Example Usage

```json
{
  "entryId": 42
}
```

## Notes

- Returns full entry data including custom fields
- Use `search_content` to find entry IDs if you don't know them
- All field data is returned in the entry's array representation
- Throws an error if the entry ID doesn't exist
