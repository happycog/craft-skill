# Field Layout Simplification

## Background

The current `update_field_layout` tool requires agents to work with complex nested array structures representing entire field layout configurations. This approach has several problems:

1. **High Complexity**: Agents must understand Craft's internal field layout structure including tabs, elements, UIDs, and type classes
2. **Error-Prone**: Requires calling `get_field_layout` first, modifying the complete structure, then passing it back - any mistake results in data loss
3. **Destructive by Default**: Forgetting to include existing elements when updating causes those elements to be deleted
4. **Verbose**: Simple operations like "add a field after another field" require dozens of lines of complex array manipulation

Example of current complexity:
```php
// Just to add ONE field, agents must:
// 1. Call get_field_layout to get existing structure
// 2. Find the right tab in the structure
// 3. Preserve all existing element UIDs and properties
// 4. Insert new field config at the right position
// 5. Pass entire modified structure back to update_field_layout
```

This specification proposes replacing the single `update_field_layout` tool with discrete, single-purpose tools that perform specific field layout operations.

## Goal

Replace the complex `update_field_layout` tool with simpler, more focused tools that:

1. Allow adding fields to field layouts with simple positional parameters
2. Support adding UI elements (headings, tips, etc.) with straightforward configuration
3. Support tab management (add, position tabs)
4. Support removing any field layout element by UID
5. Support moving/reordering elements within and across tabs
6. Eliminate the need to retrieve and reconstruct entire field layout structures
7. Prevent accidental deletion of existing field layout elements
8. Make field layout operations accessible to agents with clear, predictable behavior

## Implementation Requirements

### 1. Remove Existing Tool

**Remove `UpdateFieldLayout` tool and its controller endpoint**
- Delete `src/tools/UpdateFieldLayout.php`
- Remove `PUT /api/field-layouts/<id>` endpoint from `FieldLayoutsController`
- Remove corresponding route registration from `Plugin.php`
- Mark `update_field_layout` as deprecated in SKILLS documentation

### 2. Create `AddTabToFieldLayout` Tool

**File**: `src/tools/AddTabToFieldLayout.php`

**Purpose**: Add a new tab to a field layout at a specific position

**Parameters**:
- `fieldLayoutId` (int, required): The ID of the field layout to modify
- `name` (string, required): The name of the new tab
- `position` (array, required): Positioning configuration:
  - `type`: One of `'before'`, `'after'`, `'prepend'`, `'append'` (required - no default)
  - `tabName`: (string, optional): Name of existing tab to position relative to (required for `'before'` and `'after'`)

**Return Value**:
```php
[
    '_notes' => ['Tab added successfully', 'Review the field layout in the control panel'],
    'fieldLayout' => [...], // Updated field layout structure (same as GetFieldLayout)
]
```

**Examples**:
```php
// Append tab to end of tabs
add_tab_to_field_layout(fieldLayoutId: 123, name: 'SEO', position: ['type' => 'append'])

// Prepend tab to beginning
add_tab_to_field_layout(fieldLayoutId: 123, name: 'Settings', position: ['type' => 'prepend'])

// Add tab after specific tab
add_tab_to_field_layout(fieldLayoutId: 123, name: 'Advanced', position: ['type' => 'after', 'tabName' => 'Content'])

// Add tab before specific tab
add_tab_to_field_layout(fieldLayoutId: 123, name: 'Basic', position: ['type' => 'before', 'tabName' => 'Advanced'])
```

### 3. Create `AddFieldToFieldLayout` Tool

**File**: `src/tools/AddFieldToFieldLayout.php`

**Purpose**: Add a custom field to a field layout at a specific position within a tab

**Parameters**:
- `fieldLayoutId` (int, required): The ID of the field layout to modify
- `fieldId` (int, required): The ID of the custom field to add
- `tabName` (string, required): Name of tab to add field to (must exist - use `add_tab_to_field_layout` first if needed)
- `position` (array, required): Positioning configuration:
  - `type`: One of `'before'`, `'after'`, `'prepend'`, `'append'` (required - no default)
  - `elementUid`: (string, optional): UID of existing element to position relative to (required for `'before'` and `'after'`)
- `width` (int, optional): Field width percentage (1-100, default: 100)
- `required` (bool, optional): Whether field is required (default: false)
- `label` (string|null, optional): Custom field label override
- `instructions` (string|null, optional): Custom field instructions override
- `tip` (string|null, optional): Field tip text
- `warning` (string|null, optional): Field warning text

**Return Value**:
```php
[
    '_notes' => ['Field added successfully', 'Review the field layout in the control panel'],
    'fieldLayout' => [...], // Updated field layout structure (same as GetFieldLayout)
    'addedElement' => ['uid' => '...', 'fieldId' => ..., 'fieldHandle' => '...']
]
```

**Positioning Examples**:
```php
// Prepend to beginning of specific tab
add_field_to_field_layout(
    fieldLayoutId: 123, 
    fieldId: 456, 
    tabName: 'Content', 
    position: ['type' => 'prepend']
)

// Append to end of specific tab
add_field_to_field_layout(
    fieldLayoutId: 123, 
    fieldId: 456, 
    tabName: 'Content', 
    position: ['type' => 'append']
)

// Add after specific element by UID
add_field_to_field_layout(
    fieldLayoutId: 123, 
    fieldId: 456, 
    tabName: 'Content',
    position: ['type' => 'after', 'elementUid' => 'abc-123']
)

// Add before specific element with custom width and required
add_field_to_field_layout(
    fieldLayoutId: 123, 
    fieldId: 456, 
    tabName: 'Content',
    position: ['type' => 'before', 'elementUid' => 'def-456'], 
    width: 50, 
    required: true
)
```

### 4. Create `AddUiElementToFieldLayout` Tool

**File**: `src/tools/AddUiElementToFieldLayout.php`

**Purpose**: Add a UI element (heading, tip, markdown, etc.) to a field layout at a specific position within a tab

**Parameters**:
- `fieldLayoutId` (int, required): The ID of the field layout to modify
- `elementType` (string, required): One of:
  - `'craft\fieldlayoutelements\Heading'`
  - `'craft\fieldlayoutelements\Tip'`
  - `'craft\fieldlayoutelements\Markdown'`
  - `'craft\fieldlayoutelements\Template'`
  - `'craft\fieldlayoutelements\HorizontalRule'`
  - `'craft\fieldlayoutelements\LineBreak'`
- `tabName` (string, required): Name of tab to add element to (must exist - use `add_tab_to_field_layout` first if needed)
- `position` (array, required): Positioning configuration:
  - `type`: One of `'before'`, `'after'`, `'prepend'`, `'append'` (required - no default)
  - `elementUid`: (string, optional): UID of existing element to position relative to (required for `'before'` and `'after'`)
- `width` (int, optional): Element width percentage (1-100, default: 100) - only for elements that support custom width
- `config` (array, optional): Element-specific configuration:
  - **Heading**: `['heading' => 'Heading Text']` (required)
  - **Tip**: `['tip' => 'Tip text']` (required), `['style' => 'tip'|'warning']` (optional, default: 'tip'), `['dismissible' => true|false]` (optional, default: false)
  - **Markdown**: `['content' => 'Markdown content']` (required), `['displayInPane' => true|false]` (optional, default: true)
  - **Template**: `['template' => 'path/to/template']` (required)
  - **HorizontalRule**: No config needed
  - **LineBreak**: No config needed

**Return Value**:
```php
[
    '_notes' => ['UI element added successfully', 'Review the field layout in the control panel'],
    'fieldLayout' => [...], // Updated field layout structure
    'addedElement' => ['uid' => '...', 'type' => 'craft\fieldlayoutelements\Heading', ...]
]
```

**Examples**:
```php
// Add heading at end of tab
add_ui_element_to_field_layout(
    fieldLayoutId: 123, 
    elementType: 'craft\fieldlayoutelements\Heading', 
    tabName: 'Content',
    position: ['type' => 'append'],
    config: ['heading' => 'Additional Information']
)

// Add tip after specific field element
add_ui_element_to_field_layout(
    fieldLayoutId: 123, 
    elementType: 'craft\fieldlayoutelements\Tip',
    tabName: 'Content',
    position: ['type' => 'after', 'elementUid' => 'field-uid-123'],
    config: ['tip' => 'This field is important!', 'style' => 'warning', 'dismissible' => true]
)

// Add horizontal rule at beginning of tab
add_ui_element_to_field_layout(
    fieldLayoutId: 123, 
    elementType: 'craft\fieldlayoutelements\HorizontalRule',
    tabName: 'Content',
    position: ['type' => 'prepend']
)

// Add markdown in specific tab with custom width
add_ui_element_to_field_layout(
    fieldLayoutId: 123,
    elementType: 'craft\fieldlayoutelements\Markdown',
    tabName: 'Help',
    position: ['type' => 'append'],
    width: 50,
    config: ['content' => '## Getting Started\n\nFollow these steps...', 'displayInPane' => true]
)
```

### 5. Create `RemoveElementFromFieldLayout` Tool

**File**: `src/tools/RemoveElementFromFieldLayout.php`

**Purpose**: Remove any element (field or UI element) from a field layout by its UID

**Parameters**:
- `fieldLayoutId` (int, required): The ID of the field layout to modify
- `elementUid` (string, required): The UID of the element to remove

**Return Value**:
```php
[
    '_notes' => ['Element removed successfully', 'Review the field layout in the control panel'],
    'fieldLayout' => [...], // Updated field layout structure
]
```

**Examples**:
```php
// Remove any element by UID
remove_element_from_field_layout(fieldLayoutId: 123, elementUid: 'abc-123-def')
```

### 6. Create `MoveElementInFieldLayout` Tool

**File**: `src/tools/MoveElementInFieldLayout.php`

**Purpose**: Move an existing element to a new position within the same tab or to a different tab

**Parameters**:
- `fieldLayoutId` (int, required): The ID of the field layout to modify
- `elementUid` (string, required): The UID of the element to move
- `tabName` (string, required): Name of the target tab to move the element to
- `position` (array, required): Positioning configuration:
  - `type`: One of `'before'`, `'after'`, `'prepend'`, `'append'` (required - no default)
  - `elementUid`: (string, optional): UID of existing element to position relative to (required for `'before'` and `'after'`)

**Return Value**:
```php
[
    '_notes' => ['Element moved successfully', 'Review the field layout in the control panel'],
    'fieldLayout' => [...], // Updated field layout structure
]
```

**Examples**:
```php
// Move element to beginning of same tab
move_element_in_field_layout(
    fieldLayoutId: 123, 
    elementUid: 'element-abc', 
    tabName: 'Content',
    position: ['type' => 'prepend']
)

// Move element to different tab, after specific element
move_element_in_field_layout(
    fieldLayoutId: 123, 
    elementUid: 'element-abc', 
    tabName: 'SEO',
    position: ['type' => 'after', 'elementUid' => 'element-xyz']
)

// Move element to end of different tab
move_element_in_field_layout(
    fieldLayoutId: 123, 
    elementUid: 'element-abc', 
    tabName: 'Advanced',
    position: ['type' => 'append']
)
```

### 7. Create Controller Endpoints

**File**: `src/controllers/FieldLayoutsController.php`

Add new actions:
```php
public function actionAddTab(int $id): Response
{
    $tool = \Craft::$container->get(AddTabToFieldLayout::class);
    return $this->callTool($tool->add(...), ['fieldLayoutId' => $id]);
}

public function actionAddField(int $id): Response
{
    $tool = \Craft::$container->get(AddFieldToFieldLayout::class);
    return $this->callTool($tool->add(...), ['fieldLayoutId' => $id]);
}

public function actionAddUiElement(int $id): Response
{
    $tool = \Craft::$container->get(AddUiElementToFieldLayout::class);
    return $this->callTool($tool->add(...), ['fieldLayoutId' => $id]);
}

public function actionRemoveElement(int $id): Response
{
    $tool = \Craft::$container->get(RemoveElementFromFieldLayout::class);
    return $this->callTool($tool->remove(...), ['fieldLayoutId' => $id]);
}

public function actionMoveElement(int $id): Response
{
    $tool = \Craft::$container->get(MoveElementInFieldLayout::class);
    return $this->callTool($tool->move(...), ['fieldLayoutId' => $id]);
}
```

Remove existing action:
```php
// DELETE: public function actionUpdate(int $id): Response { ... }
```

### 8. Register Routes

**File**: `src/Plugin.php`

Add routes:
```php
$event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/tabs'] = 'mcp/field-layouts/add-tab';
$event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/fields'] = 'mcp/field-layouts/add-field';
$event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/ui-elements'] = 'mcp/field-layouts/add-ui-element';
$event->rules['DELETE ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'mcp/field-layouts/remove-element';
$event->rules['PUT ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'mcp/field-layouts/move-element';
```

Remove route:
```php
// DELETE: $event->rules['PUT ' . $apiPrefix . '/field-layouts/<id>'] = 'mcp/field-layouts/update';
```

## Technical Implementation Notes

### Field Layout Structure

Craft field layouts consist of:
- **FieldLayout**: Container with an ID and type (e.g., `craft\elements\Entry`)
- **FieldLayoutTab**: Named tabs that organize elements (e.g., "Content", "SEO", "Metadata")
- **FieldLayoutElement**: Elements within tabs, which can be:
  - **CustomField**: References a Craft field by ID
  - **BaseNativeField**: Built-in element fields (title, slug, etc.)
  - **BaseUiElement**: UI elements (headings, tips, markdown, etc.)

Each element has:
- `uid`: Unique identifier for the element instance in the layout
- `width`: Display width (1-100 percentage)
- Element-specific properties (field config, UI element content, etc.)

### Positioning Logic

The positioning system works as follows:

1. **Retrieve existing field layout**: Use `Craft::$app->getFields()->getLayoutById($fieldLayoutId)`
2. **Locate target tab**: Find the specified tab by name (must exist - error if not found)
3. **Create new element** (for add operations):
   - For fields: `new CustomField($field)` where `$field = Craft::$app->getFields()->getFieldById($fieldId)`
   - For UI elements: Instantiate class by name (e.g., `new Heading()`) and set properties from `config`
4. **Position element**: Based on position configuration:
   - `'prepend'`: Insert at beginning of tab's elements array
   - `'append'`: Insert at end of tab's elements array
   - `'before'`: Find element with matching UID within the target tab, insert before it (error if UID not found in tab)
   - `'after'`: Find element with matching UID within the target tab, insert after it (error if UID not found in tab)
5. **Update field layout**: Call `$fieldLayout->setTabs($tabs)` and `Craft::$app->getFields()->saveLayout($fieldLayout)`

### Tab Positioning Logic

For adding tabs to field layouts:

1. **Retrieve existing field layout**: Use `Craft::$app->getFields()->getLayoutById($fieldLayoutId)`
2. **Create new tab**: Instantiate `new FieldLayoutTab(['layout' => $fieldLayout, 'name' => $name, 'elements' => []])`
3. **Position tab**: Based on position configuration:
   - `'prepend'`: Insert at beginning of field layout's tabs array
   - `'append'`: Insert at end of field layout's tabs array
   - `'before'`: Find tab with matching name, insert before it (error if tab name not found)
   - `'after'`: Find tab with matching name, insert after it (error if tab name not found)
4. **Update field layout**: Call `$fieldLayout->setTabs($tabs)` and `Craft::$app->getFields()->saveLayout($fieldLayout)`

### Element Identification

Elements within field layouts are **exclusively identified by their `uid` property** (not field ID). 

**Critical workflow**:
1. Agents MUST call `get_field_layout` first to retrieve current structure and identify element UIDs
2. Reference elements by their UID in position configuration
3. Field IDs are only used when adding new CustomField elements (to specify which field to add)
4. Tab names are used for tab positioning and element placement

**No shorthand references**: The tools do not support shorthand references like `"field:123"` - only explicit UIDs from `get_field_layout` output

### Tab Management

Tabs must be **explicitly created** using `add_tab_to_field_layout` before adding elements to them:
- If `tabName` doesn't exist when adding fields/UI elements, the tool returns an error
- Agents should call `add_tab_to_field_layout` first to create the tab
- This prevents accidental tab creation and gives agents explicit control over tab structure

### Error Handling

All tools must validate inputs and provide clear error messages:

- **Field Layout Validation**: Validate `fieldLayoutId` exists using `Craft::$app->getFields()->getLayoutById()`
- **Field Validation**: Validate `fieldId` exists (for `AddFieldToFieldLayout`)
- **Tab Validation**: Validate `tabName` exists in field layout (error if not found - agent must create tab first)
- **Element Type Validation**: Validate `elementType` is valid UI element class (for `AddUiElementToFieldLayout`)
- **Element UID Validation**: Validate `elementUid` exists in specified tab (for before/after positioning)
- **Position Validation**: Validate position `type` is one of the allowed values
- **Position Context Validation**: Ensure `elementUid` is provided when using 'before' or 'after'
- **Tab Position Validation**: Ensure `tabName` is provided when using 'before' or 'after' for tab positioning
- **Config Validation**: Validate required config properties are provided for UI elements (e.g., `heading` for Heading, `tip` for Tip)
- **Model Save Failures**: Use `ModelSaveException` for field layout save failures
- **Duplicate Prevention**: Optionally validate that the same field isn't already in the layout (to prevent duplicate fields)

### Return Values

All tools should return consistent structures:

**Add tools** (`AddTab`, `AddField`, `AddUiElement`):
1. Success message with control panel link suggestion
2. Complete updated field layout structure (using same format as `GetFieldLayout`)
3. Information about the newly added element/tab (UID, type, relevant properties)

**Remove tool** (`RemoveElement`):
1. Success message confirming removal
2. Complete updated field layout structure

**Move tool** (`MoveElement`):
1. Success message confirming move
2. Complete updated field layout structure

### Control Panel URLs

Since field layouts can be associated with different models (entry types, global sets, users, etc.), control panel URLs should be omitted. Users should be directed to review "the relevant field layout settings in the control panel" without specific URLs.

## Non-Requirements (Future Considerations)

The following features are explicitly out of scope for this specification:

1. **Updating Element Properties**: No ability to modify existing element configuration in-place - users must remove and re-add with new configuration
2. **Tab Removal**: No `remove_tab_from_field_layout` tool - would require separate spec and consideration of what happens to tab contents
3. **Tab Renaming**: No ability to rename existing tabs - would require separate spec
4. **Bulk Operations**: No ability to add multiple fields/elements in one call - keep tools focused on single operations
5. **Field Layout Cloning**: No ability to copy field layouts between models
6. **Conditional Element Display**: No support for Craft's conditional element display rules
7. **Native Field Management**: Only supports adding custom fields, not native fields (title, slug, etc.)
8. **Implicit Tab Creation**: Tabs must be explicitly created - no automatic tab creation when specifying non-existent tab names
9. **Field Duplication Prevention**: No automatic validation to prevent adding the same field twice to a layout (this is Craft's responsibility)

## Acceptance Criteria

### Functional Requirements

1. ✅ Agents can create new tabs with explicit positioning (before, after, prepend, append)
2. ✅ Agents can add custom fields to specific tabs with precise positioning
3. ✅ Agents can add UI elements (heading, tip, markdown, template, horizontal rule, line break) to specific tabs
4. ✅ Agents can specify position relative to existing elements using element UIDs retrieved from `get_field_layout`
5. ✅ Agents can remove any element (field or UI element) by its UID
6. ✅ Agents can move elements between tabs or reorder within a tab
7. ✅ Tools require explicit tab names - no implicit tab creation when adding elements
8. ✅ Position parameter is always required - no default positioning behavior
9. ✅ Tools prevent accidental deletion of existing field layout elements
10. ✅ Field layout modifications are persisted to the database
11. ✅ All tools return updated field layout structure matching `GetFieldLayout` format

### API Requirements

1. ✅ `POST /api/field-layouts/<id>/tabs` endpoint creates new tabs
2. ✅ `POST /api/field-layouts/<id>/fields` endpoint adds fields to layouts
3. ✅ `POST /api/field-layouts/<id>/ui-elements` endpoint adds UI elements to layouts
4. ✅ `DELETE /api/field-layouts/<id>/elements` endpoint removes elements by UID
5. ✅ `PUT /api/field-layouts/<id>/elements` endpoint moves elements to new positions
6. ✅ All endpoints validate input parameters using Valinor
7. ✅ All endpoints return 400 status for invalid input (missing tabs, invalid UIDs, etc.)
8. ✅ All endpoints return 200 status with updated field layout on success

### Code Quality Requirements

1. ✅ New tools follow existing tool patterns (constructor injection, proper type annotations)
2. ✅ PHPStan level max compliance with no errors
3. ✅ Proper error handling using `ModelSaveException` and `throw_unless` patterns
4. ✅ Tools use dependency injection for Craft services
5. ✅ Full PHPDoc annotations for all parameters and return types

### Testing Requirements

**Tab Management Tests**:
1. ✅ Test adding tab with prepend positioning
2. ✅ Test adding tab with append positioning
3. ✅ Test adding tab before/after existing tab
4. ✅ Test error when positioning tab relative to non-existent tab

**Field Addition Tests**:
5. ✅ Test adding field with prepend/append to existing tab
6. ✅ Test adding field with before/after positioning relative to element UID
7. ✅ Test adding field with custom configuration (width, required, label, etc.)
8. ✅ Test error when adding field to non-existent tab
9. ✅ Test error when positioning field relative to non-existent element UID

**UI Element Addition Tests**:
10. ✅ Test adding each UI element type (heading, tip, markdown, template, horizontal rule, line break)
11. ✅ Test UI element with all required config properties
12. ✅ Test UI element with optional config properties
13. ✅ Test UI element positioning (prepend/append/before/after)
14. ✅ Test error when UI element missing required config (e.g., heading text)

**Element Removal Tests**:
15. ✅ Test removing field by UID
16. ✅ Test removing UI element by UID
17. ✅ Test error when removing with non-existent UID
18. ✅ Test that other elements are preserved after removal

**Element Movement Tests**:
19. ✅ Test moving element within same tab (reordering)
20. ✅ Test moving element to different tab
21. ✅ Test moving element with before/after positioning
22. ✅ Test error when moving to non-existent tab
23. ✅ Test error when moving non-existent element UID

**General Tests**:
24. ✅ Test that existing field layout elements are preserved across all operations
25. ✅ Test error conditions (invalid fieldLayoutId, invalid fieldId)
26. ✅ Test return value structure matches specification for all tools
27. ✅ Verify database persistence (field layout changes are saved)

### Documentation Requirements

1. ✅ Update SKILLS.md to document new tools and deprecate old tool
2. ✅ Update AGENTS.md with field layout simplification patterns
3. ✅ Remove or deprecate old `update_field_layout` examples
4. ✅ Add examples of new tool usage patterns
