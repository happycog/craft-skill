# Unify GetSections and GetEntryTypes Tools

## Background

The current `GetEntryTypes` tool has grown complex with filtering logic for `$sectionId` and `$includeStandalone` parameters, making it difficult to use effectively. Meanwhile, `GetSections` provides basic entry type information but lacks field details that are crucial for understanding content structure. This creates inconsistent APIs and forces users to make multiple tool calls to get complete information.

## Goal

Simplify the GetSections and GetEntryTypes tools to provide consistent, comprehensive content structure information with intuitive filtering capabilities and complete field details for all entry types.

## Implementation Requirements

### 1. Simplify GetEntryTypes Tool
- Remove the `$sectionId` parameter (complex filtering logic)
- Remove the `$includeStandalone` parameter (eliminates categorization complexity)
- Add `array<int> $entryTypeIds` parameter to limit results to specific entry types
- Return complete field information for each entry type including:
  - Field name, handle, description
  - Required status from field layout
  - Field type class name

### 2. Enhance GetSections Tool
- Add `array<int> $sectionIds` parameter to limit results to specific sections
- Include complete field information for each entry type within sections
- Match field formatting from GetEntryTypes for consistency
- Maintain existing section metadata (id, handle, name, type)

### 3. Field Information Standardization
- Both tools should return identical field data structure
- Leverage existing GetFields tool formatting logic
- Include field layout context (required status, width, tab information)
- Handle nested fields (Matrix blocks) consistently

## Technical Implementation Notes

### Field Layout Integration
- Use `$entryType->getFieldLayout()->getCustomFields()` to get fields with layout context
- Extract required status from field layout tabs, not just field definitions
- Maintain field ordering as defined in the field layout

### Parameter Validation
- Both tools should accept empty arrays or `null` as valid filtering parameters (return all results)
- Do not validate provided IDs. Craft will handle that for us.
- Use Craft's element query patterns for efficient filtering

### Response Structure Consistency
- Both tools should return similar nested structures for entry types
- Include usage statistics (entry count, draft count) in both tools
- Provide edit URLs for control panel navigation
- The logic in `GetFields::formatField()` should be abstracted out to an `happycog\craftmcp\actions` class that can be called from GetFields, GetEntryTypes, and GetSections

## Non-Requirements (Future Considerations)

- Advanced field filtering (by field type, usage, etc.)
- Bulk field operations across multiple entry types
- Field dependency mapping
- Performance optimization for large field sets

## Acceptance Criteria

- [ ] GetEntryTypes accepts `array<int> $entryTypeIds` parameter and returns complete field information
- [ ] GetSections accepts `array<int> $sectionIds` parameter and includes field details for all entry types
- [ ] Both tools return identical field data structure with name, handle, description, required status, and type
- [ ] Both tools return nested Matrix fields with nested entry type structures/fields
- [ ] Field layout context (required status, ordering) is preserved in responses
- [ ] Tests pass for both modified tools
- [ ] PHPStan analysis passes at max level
