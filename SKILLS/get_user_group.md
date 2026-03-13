# get_user_group

Retrieve a Craft user group by ID or handle.

## Endpoint

`GET /api/user-groups/<id>`

## Parameters

- `groupId` (int, optional) - Group ID
- `handle` (string, optional) - Group handle

## Returns

Returns the resolved user group with handle, description, user count, permissions, and CP URL.

## Notes

- Provide exactly one of `groupId` or `handle`.
- User groups require Craft Pro.

## Example

```json
{
  "handle": "editors"
}
```
