# get_addresses

List Address elements, with optional filtering by owner, address field, and location.

## Endpoint

`get_addresses`

## Parameters

- `ownerId` (int, optional) - Owner element ID; must be paired with `ownerType`
- `ownerType` (string, optional) - Owner element class name such as `craft\elements\User`
- `fieldId` (int, optional) - Restrict results to a specific `craft\fields\Addresses` field
- `fieldHandle` (string, optional) - Alternative to `fieldId`
- `countryCode` (string, optional) - Filter by ISO country code
- `postalCode` (string, optional) - Filter by postal code
- `locality` (string, optional) - Filter by city/locality
- `limit` (int, optional) - Maximum number of results; defaults to `10`

## Returns

Returns matching addresses with owner details, field linkage, native address attributes, and serialized custom field values.

## Example

```json
{
  "ownerId": 12,
  "ownerType": "craft\\elements\\User",
  "countryCode": "US",
  "limit": 20
}
```
