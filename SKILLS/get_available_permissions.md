# get_available_permissions

List all available Craft user permissions, grouped the same way Craft registers them, plus any custom permission names already stored in the database.

## Endpoint

`GET /api/users/permissions`

## Parameters

None.

## Returns

Returns:

- `groups` - Permission groups with headings, labels, info, warnings, and nested permissions
- `allPermissions` - Flat permission list with user-facing labels, info, warnings, and group headings
- `allPermissionNames` - Flat list of all registered and stored permission names
- `customPermissions` - Flat list of stored custom permissions with labels and group heading metadata
- `customPermissionNames` - Stored permission names that are not part of Craft's registered permission tree

## Notes

- Custom permission names appear after they have been assigned to a user or user group.

## Example

```json
{}
```
