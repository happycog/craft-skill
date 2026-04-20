# get_users

List Craft users, with optional filters for search text, email, username, status, and user group.

## Endpoint

`get_users`

## Parameters

- `query` (string, optional) - Search text for Craft's user element query
- `email` (string, optional) - Exact email filter
- `username` (string, optional) - Exact username filter
- `status` (string, optional) - User status filter
- `groupId` (int, optional) - Filter by user group ID; requires Craft Team or Pro
- `groupHandle` (string, optional) - Filter by user group handle; requires Craft Team or Pro
- `limit` (int, optional) - Maximum users to return, default `25`

## Returns

Returns a `results` array of formatted users, including native attributes, current groups, permissions, and custom fields.

## Notes

- Provide only one of `groupId` or `groupHandle`.
- Group-based filtering is unavailable on Craft Solo.

## Example

```json
{
  "status": "active",
  "limit": 10
}
```
