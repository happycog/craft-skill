# update_user

Update a Craft user resolved by ID, email, or username.

## Endpoint

`PUT /api/users/<id>`

## Parameters

- One identifier is required: `userId` (int), `email` (string), or `username` (string)
- `newEmail` (string, optional) - Replacement email
- `newUsername` (string, optional) - Replacement username
- `newPassword` (string, optional) - Replacement password
- `fullName` (string, optional) - Updated full name
- `firstName` (string, optional) - Updated first name
- `lastName` (string, optional) - Updated last name
- `admin` (bool, optional) - Updated admin flag
- `active` (bool, optional) - Activate/deactivate the user
- `pending` (bool, optional) - Updated pending flag
- `suspended` (bool, optional) - Suspend/unsuspend the user
- `locked` (bool, optional) - Set `false` to unlock the user
- `affiliatedSiteId` (int, optional) - Updated affiliated site ID
- `groupIds` (array<int>, optional) - Replace user groups; requires Craft Team or Pro
- `groupHandles` (array<string>, optional) - Replace user groups by handle; requires Craft Team or Pro
- `permissions` (array<string>, optional) - Replace direct user permissions; requires Craft Pro
- `fields` (object, optional) - Updated custom field values

## Returns

Returns the updated user with native attributes, groups, permissions, and custom fields.

## Notes

- Provide exactly one identifier.
- Provide only one of `groupIds` or `groupHandles`.
- Direct permission assignment is unavailable outside Craft Pro.

## Example

```json
{
  "username": "editor@example.com",
  "fullName": "Updated User"
}
```
