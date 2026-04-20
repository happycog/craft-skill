# delete_user_group

Delete a Craft user group by ID or handle.

## Endpoint

`delete_user_group`

## Parameters

- `groupId` (int, optional) - Group ID
- `handle` (string, optional) - Group handle

## Returns

Returns the deleted group details.

## Notes

- Provide exactly one of `groupId` or `handle`.
- User groups require Craft Pro.

## Example

```json
{
  "handle": "temporary-group"
}
```
