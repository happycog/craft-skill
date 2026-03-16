# create_user

Create a Craft user with native attributes and optional custom field values.

## Endpoint

`POST /api/users`

## Parameters

- `email` (string, required) - User email address
- `username` (string, optional) - Username; defaults to the email
- `newPassword` (string, optional) - Initial password
- `fullName` (string, optional) - Full name
- `firstName` (string, optional) - First name
- `lastName` (string, optional) - Last name
- `admin` (bool, optional) - Admin flag, default `false`
- `active` (bool, optional) - Active flag, default `true`
- `pending` (bool, optional) - Pending flag, default `false`
- `suspended` (bool, optional) - Suspended flag, default `false`
- `locked` (bool, optional) - Locked flag, default `false`
- `affiliatedSiteId` (int, optional) - Affiliated site ID
- `groupIds` (array<int>, optional) - User group IDs; requires Craft Team or Pro
- `groupHandles` (array<string>, optional) - User group handles; requires Craft Team or Pro
- `permissions` (array<string>, optional) - Direct user permissions; requires Craft Pro
- `fields` (object, optional) - Custom field values from the global user field layout

## Returns

Returns the created user with native attributes, groups, permissions, and custom fields.

## Notes

- User creation still respects Craft edition user-count limits.
- Provide only one of `groupIds` or `groupHandles`.
- Direct permission assignment is unavailable outside Craft Pro.

## Example

```json
{
  "email": "new-user@example.com",
  "newPassword": "Password123!",
  "fullName": "New User"
}
```
