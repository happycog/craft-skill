# delete_user

Delete a Craft user by ID, email, or username.

## Endpoint

`DELETE /api/users/<id>`

## Parameters

- `userId` (int, optional) - User ID
- `email` (string, optional) - Resolve by email
- `username` (string, optional) - Resolve by username
- `permanentlyDelete` (bool, optional) - Permanently delete instead of soft-deleting, default `false`

## Returns

Returns the deleted user's serialized details and whether the delete was permanent.

## Notes

- Provide exactly one of `userId`, `email`, or `username`.

## Example

```json
{
  "email": "former-user@example.com"
}
```
