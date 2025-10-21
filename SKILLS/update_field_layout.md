# update_field_layout [DEPRECATED]

> **⚠️ DEPRECATED**: This monolithic update approach has been replaced with granular field layout tools.

## Migration Guide

The `update_field_layout` tool has been replaced with five specialized tools for more precise field layout management:

### New Tools

1. **add_tab_to_field_layout** - Add tabs with flexible positioning
2. **add_field_to_field_layout** - Add fields to tabs with positioning and configuration
3. **add_ui_element_to_field_layout** - Add UI elements (headings, tips, rules, etc.)
4. **move_element_in_field_layout** - Move elements within or between tabs
5. **remove_element_from_field_layout** - Remove elements from layouts

### Why the Change?

The new granular approach provides:
- **Better control**: Make incremental changes without replacing entire layouts
- **Safer operations**: Avoid accidentally removing elements
- **More flexible**: Position elements precisely with before/after/prepend/append
- **Clearer intent**: Each operation has a specific purpose
- **Better error handling**: Validation at the operation level

### Migration Examples

#### Old Approach (Deprecated)
```json
{
  "fieldLayoutId": 1,
  "tabs": [
    {
      "name": "Content",
      "fields": [
        {"fieldId": 1, "required": true},
        {"fieldId": 2, "required": false}
      ]
    }
  ]
}
```

#### New Approach (Recommended)
```bash
# 1. Add a tab
add_tab_to_field_layout {
  "fieldLayoutId": 1,
  "name": "Content",
  "position": {"type": "append"}
}

# 2. Add fields one by one
add_field_to_field_layout {
  "fieldLayoutId": 1,
  "fieldId": 1,
  "tabName": "Content",
  "position": {"type": "append"},
  "required": true
}

add_field_to_field_layout {
  "fieldLayoutId": 1,
  "fieldId": 2,
  "tabName": "Content",
  "position": {"type": "append"},
  "required": false
}
```

## See Also

- [add_tab_to_field_layout](add_tab_to_field_layout.md)
- [add_field_to_field_layout](add_field_to_field_layout.md)
- [add_ui_element_to_field_layout](add_ui_element_to_field_layout.md)
- [move_element_in_field_layout](move_element_in_field_layout.md)
- [remove_element_from_field_layout](remove_element_from_field_layout.md)
