# delete_address

Delete an Address element.

## Endpoint

`DELETE /api/addresses/<id>`

## Parameters

- `addressId` (int, required) - Address element ID
- `permanentlyDelete` (bool, optional) - Permanently remove the address instead of soft-deleting it

## Returns

Returns the deleted address metadata plus whether the deletion was permanent.

## Example

```json
{
  "addressId": 42,
  "permanentlyDelete": true
}
```
