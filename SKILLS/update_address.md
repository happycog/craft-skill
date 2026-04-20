# update_address

Update an existing Address element.

## Endpoint

`update_address`

## Parameters

- `addressId` (int, required) - Address element ID
- Any native address attributes you want to change
- `fields` (object, optional) - Updated custom address field values

## Returns

Returns the updated address.

## Example

```json
{
  "addressId": 42,
  "title": "Office",
  "locality": "Seattle",
  "postalCode": "98101"
}
```
