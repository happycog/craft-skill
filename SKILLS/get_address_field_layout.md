# get_address_field_layout

Retrieve the single global field layout used by all Address elements.

## Endpoint

`get_address_field_layout`

## Parameters

None.

## Returns

Returns the global Address field layout tabs and elements, plus the control-panel settings URL.

## Notes

- Addresses use a singleton-style global field layout.
- The returned `fieldLayout.id` is a stable placeholder identifier for use with field-layout mutation tools.

## Example

```json
{}
```
