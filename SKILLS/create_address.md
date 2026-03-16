# create_address

Create an Address for a generic owner, either directly on an element or inside an `Addresses` custom field.

## Endpoint

`POST /api/addresses`

## Parameters

- `ownerId` (int, required) - Owner element ID
- `ownerType` (string, required) - Owner element class name
- `fieldId` (int, optional) - Target `craft\fields\Addresses` field ID
- `fieldHandle` (string, optional) - Alternative to `fieldId`
- Native address attributes such as `title`, `countryCode`, `administrativeArea`, `locality`, `postalCode`, `addressLine1`, `addressLine2`, `organization`, `latitude`, `longitude`
- `fields` (object, optional) - Custom address field values from the global Address field layout

## Returns

Returns the created address with ownership metadata and serialized custom fields.

## Example

```json
{
  "ownerId": 12,
  "ownerType": "craft\\elements\\User",
  "title": "Home",
  "countryCode": "US",
  "addressLine1": "123 Main St",
  "locality": "Portland",
  "administrativeArea": "OR",
  "postalCode": "97205"
}
```
