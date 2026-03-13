# update_user_group

Update a Craft user group by ID or handle.

## Endpoint

`PUT /api/user-groups/<id>`

## Parameters

- One identifier is required: `groupId` (int) or `handle` (string)
- `newName` (string, optional) - Replacement name
- `newHandle` (string, optional) - Replacement handle
- `description` (string, optional) - Replacement description
- `permissions` (array<string>, optional) - Replacement permissions, including custom permission names

## Returns

Returns the updated user group with permissions and user count.

## Notes

- User groups require Craft Pro.

## Example

```json
{
  "handle": "reviewers",
  "permissions": ["accesscp", "custompermission:review"]
}
```
