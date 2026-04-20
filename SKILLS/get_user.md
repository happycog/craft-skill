# get_user

Retrieve a single Craft user by ID, email, or username.

## Endpoint

`get_user`

## Parameters

- `userId` (int, optional) - User ID
- `email` (string, optional) - Resolve user by email
- `username` (string, optional) - Resolve user by username

## Returns

Returns the resolved user with native attributes, groups, permissions, custom fields, and control-panel URLs.

## Notes

- Provide exactly one of `userId`, `email`, or `username`.
- The route parameter `<id>` maps to `userId` automatically.

## Example

```json
{
  "email": "author@example.com"
}
```
