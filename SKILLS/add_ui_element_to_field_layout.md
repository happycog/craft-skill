# add_ui_element_to_field_layout

Add a UI element (heading, tip, horizontal rule, etc.) to a field layout.

## Description

Adds non-field UI elements to field layouts for better organization and user guidance. UI elements include headings, tips, warnings, horizontal rules, line breaks, Markdown content, and custom templates.

## Parameters

### Required Parameters

- **fieldLayoutId** (integer): Field layout ID to modify
- **elementType** (string): Type of UI element to add
  - `heading`: Section heading
  - `tip`: Information tip box
  - `horizontalRule`: Visual separator line
  - `lineBreak`: Line break for spacing
  - `markdown`: Markdown-formatted content
  - `template`: Custom Twig template
- **tabName** (string): Name of the tab to add element to (must exist)
- **position** (object): Positioning configuration
  - **type** (string): Position type - 'before', 'after', 'prepend', or 'append'
  - **elementUid** (string): UID of existing element (required for 'before' and 'after')

### Element-Specific Parameters

#### Heading
- **heading** (string, required): Heading text

#### Tip
- **tip** (string, required): Tip content text
- **style** (string): Style - 'tip' (blue), 'warning' (yellow), or 'error' (red)

#### Markdown
- **markdown** (string, required): Markdown-formatted content

#### Template
- **template** (string, required): Twig template path

### Optional Parameters (all element types)

- **width** (integer): Element width percentage (1-100), defaults to 100

## Return Value

Returns the updated field layout with the new UI element included.

## Example Usage

### Add Heading

```json
{
  "fieldLayoutId": 1,
  "elementType": "heading",
  "tabName": "Content",
  "position": {
    "type": "prepend"
  },
  "heading": "Primary Content",
  "width": 100
}
```

### Add Tip with Warning Style

```json
{
  "fieldLayoutId": 1,
  "elementType": "tip",
  "tabName": "Advanced",
  "position": {
    "type": "append"
  },
  "tip": "Changes to these settings will affect all published entries",
  "style": "warning"
}
```

### Add Horizontal Rule Separator

```json
{
  "fieldLayoutId": 1,
  "elementType": "horizontalRule",
  "tabName": "Content",
  "position": {
    "type": "after",
    "elementUid": "abc123-def456-789"
  }
}
```

### Add Markdown Content

```json
{
  "fieldLayoutId": 1,
  "elementType": "markdown",
  "tabName": "Instructions",
  "position": {
    "type": "append"
  },
  "markdown": "## Usage Guide\n\nFollow these steps:\n1. Fill in required fields\n2. Review content\n3. Publish"
}
```

### Add Custom Template

```json
{
  "fieldLayoutId": 1,
  "elementType": "template",
  "tabName": "Metadata",
  "position": {
    "type": "prepend"
  },
  "template": "_components/field-layout/custom-widget"
}
```

## Notes

- **IMPORTANT**: Always use `get_field_layout` first to check which tabs already exist in the field layout
- The target tab must already exist - if it doesn't, use `add_tab_to_field_layout` to create it
- UI elements help organize and document field layouts for content editors
- Tip styles provide visual hierarchy:
  - `tip` (blue): General information
  - `warning` (yellow): Important notices
  - `error` (red): Critical warnings
- Markdown elements support full Markdown syntax
- Template elements can render custom Twig templates for dynamic content
- Horizontal rules and line breaks are useful for visual organization
- Position types:
  - `prepend`: Add as first element in tab
  - `append`: Add as last element in tab
  - `before`: Insert before a specific element UID
  - `after`: Insert after a specific element UID
- Changes are immediately saved to the field layout
