# create_user_group

Create a Craft user group and optionally set its permissions.

## Endpoint

`create_user_group`

## Parameters

- `name` (string, required) - Group name
- `handle` (string, optional) - Group handle; defaults to a kebab-case version of the name
- `description` (string, optional) - Group description
- `permissions` (array<string>, optional) - Group permissions, including custom permission names

## Returns

Returns the created user group with permissions and user count.

## Notes

- User groups require Craft Pro.
- Permission names are normalized to lowercase.

## Example

```json
{
  "name": "Publishers",
  "permissions": ["accesscp", "custompermission:publish"]
}
```
