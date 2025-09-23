# Section and Entry Type Management Tools

## Background

The current MCP server provides read-only access to Craft CMS sections and entry types through the `GetSections` tool, but lacks the ability to create, update, or delete sections and entry types. Content architects and developers frequently need to programmatically manage site structure, especially when setting up new content types, migrating content architectures, or maintaining multiple environments.

Craft CMS sections define the structural organization of content with different types (Single, Channel, Structure), while entry types define the content schema within sections through field layouts. The relationship between sections, entry types, and field layouts is hierarchical: sections contain entry types, and entry types have field layouts that organize fields.

Currently, users must manually navigate to the Craft control panel to create sections and configure entry types, which is time-consuming and error-prone when managing complex content architectures or performing bulk operations.

## Goal

Create comprehensive MCP tools that enable complete section and entry type lifecycle management in Craft CMS, allowing users to programmatically create, modify, and organize content architecture while providing seamless integration with the existing field management system.

## Implementation Requirements

### 1. Create Section Tool (`CreateSection`)
- Support all section types: Single, Channel, Structure
- Configure section properties (name, handle, type, URI format)
- Set up site-specific settings for multi-site installations
- Create default entry type automatically or optionally skip
- Return control panel URL for section review and further configuration
- Support preview targets and propagation method configuration

### 2. Update Section Tool (`UpdateSection`)
- Modify section properties (name, handle, URI format, type with restrictions)
- Update site-specific settings (hasUrls, uriFormat, template)
- Change section type where technically feasible (with data preservation warnings)
- Support enabling/disabling sections temporarily
- Preserve entry data during safe modifications

### 3. Delete Section Tool (`DeleteSection`)
- Remove sections with proper cleanup of related data
- Support both soft delete and permanent deletion options
- Warn about entry content loss implications before deletion
- Handle deletion of associated entry types and field layouts
- Provide detailed impact assessment (number of entries, drafts, revisions)

### 4. Create Entry Type Tool (`CreateEntryType`)
- Create entry types within existing sections
- Configure entry type properties (name, handle, icon, color)
- Set up default field layout or create empty layout
- Support global entry types (Craft 4.4+) and section-specific entry types
- Enable entry type-specific settings (showTitles, titleTranslationMethod)

### 5. Update Entry Type Tool (`UpdateEntryType`)
- Modify entry type properties (name, handle, icon, color, settings)
- Change entry type availability across sections
- Preserve field layout during property updates
- Support showing/hiding title fields and configuring title settings

### 6. Delete Entry Type Tool (`DeleteEntryType`)
- Remove entry types with proper validation
- Prevent deletion of entry types with existing entries (unless forced)
- Clean up associated field layouts appropriately
- Provide entry usage statistics before deletion

### 7. Update Field Layout Tool (`UpdateFieldLayout`)
- Organize fields within entry type field layouts
- Support field layout tabs and conditional fields
- Add, remove, and reorder fields within layouts
- Configure field-specific settings within layout context
- Support field layout inheritance and template patterns

## Technical Implementation Notes

### Section Management API
```php
// Create section using sections service
$sectionsService = Craft::$app->getSections();
$section = new Section([
    'name' => $sectionName,
    'handle' => $sectionHandle,
    'type' => $sectionType, // Section::TYPE_SINGLE, TYPE_CHANNEL, TYPE_STRUCTURE
    'enableVersioning' => true,
    'propagationMethod' => $propagationMethod,
]);

// Configure site-specific settings
$siteSettings = [];
foreach ($sites as $site) {
    $siteSettings[$site->id] = new Section_SiteSettings([
        'siteId' => $site->id,
        'enabledByDefault' => true,
        'hasUrls' => $hasUrls,
        'uriFormat' => $uriFormat,
        'template' => $template,
    ]);
}
$section->setSiteSettings($siteSettings);

// Save section
if (!$sectionsService->saveSection($section)) {
    $errors = $section->getErrors();
}
```

### Entry Type Management API
```php
// Create entry type
$entryTypesService = Craft::$app->getSections();
$entryType = new EntryType([
    'name' => $entryTypeName,
    'handle' => $entryTypeHandle,
    'sectionId' => $sectionId, // null for global entry types
    'hasTitleField' => $hasTitleField,
    'titleTranslationMethod' => $titleTranslationMethod,
    'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
    'icon' => $icon,
    'color' => $color,
]);

// Save entry type
if (!$entryTypesService->saveEntryType($entryType)) {
    $errors = $entryType->getErrors();
}
```

### Field Layout Management API
```php
// Update field layout for entry type
$fieldsService = Craft::$app->getFields();
$fieldLayout = $entryType->getFieldLayout();

// Create new field layout with organized tabs
$tabs = [];
foreach ($tabData as $tabInfo) {
    $tab = new FieldLayoutTab([
        'layout' => $fieldLayout,
        'name' => $tabInfo['name'],
        'elements' => $this->createFieldLayoutElements($tabInfo['fields']),
    ]);
    $tabs[] = $tab;
}

$fieldLayout->setTabs($tabs);

// Save the field layout
if (!$fieldsService->saveLayout($fieldLayout)) {
    $errors = $fieldLayout->getErrors();
}
```

### Section Types and Validation
- **Single**: One entry per section, typically for homepage or about page
- **Channel**: Multiple entries with flexible structure, chronological or topical content
- **Structure**: Hierarchical entries with parent-child relationships, URLs reflect structure
- Type changes are restricted once entries exist (Single ↔ Channel possible, Structure requires migration)

### Site Settings Configuration
```php
// Multi-site section configuration
$siteSettings = [];
foreach (Craft::$app->getSites()->getAllSites() as $site) {
    $siteSettings[$site->id] = new Section_SiteSettings([
        'siteId' => $site->id,
        'enabledByDefault' => $enabledByDefault,
        'hasUrls' => $hasUrls,
        'uriFormat' => $this->processSiteSpecificUriFormat($uriFormat, $site),
        'template' => $this->processSiteSpecificTemplate($template, $site),
    ]);
}
```

### Control Panel Integration
- Section edit URLs: `{cpUrl}/settings/sections/{sectionId}`
- Entry type edit URLs: `{cpUrl}/settings/entry-types/{entryTypeId}`
- Field layout edit URLs: `{cpUrl}/settings/entry-types/{entryTypeId}/field-layout`
- Section entries index: `{cpUrl}/entries/{sectionHandle}`

### Error Handling and Validation
- Use Craft's section and entry type validation via `getErrors()` method
- Handle section type change restrictions and data migration warnings
- Validate handle uniqueness across sections and entry types
- Check section dependencies before deletion (entries, drafts, revisions)
- Validate field layout changes that might affect existing content

### Field Layout Element Management
```php
// Create field layout elements for fields
private function createFieldLayoutElements(array $fieldIds): array
{
    $elements = [];
    foreach ($fieldIds as $fieldId) {
        $field = Craft::$app->getFields()->getFieldById($fieldId);
        if ($field) {
            $elements[] = Craft::createObject([
                'class' => CustomField::class,
                'fieldId' => $fieldId,
                'required' => false, // configurable per field
                'instructions' => '', // can override field instructions
            ]);
        }
    }
    return $elements;
}
```

### Propagation Method Configuration
- **All**: Content propagates to all sites with the same values
- **Site Group**: Content propagates within site groups only
- **Language**: Content propagates to sites with the same language
- **Custom**: Uses custom propagation key format
- **None**: Each site maintains independent content

## Non-Requirements (Future Considerations)

- Advanced field layout designer with drag-and-drop interface
- Section template code generation and scaffolding
- Bulk section operations and migration tools
- Section-level permission management beyond Craft's built-in system
- Integration with version control systems for section configuration
- Section configuration import/export functionality
- Advanced section analytics and usage reporting
- Multi-environment section synchronization tools

## Acceptance Criteria

- [ ] CreateSection tool supports all section types (Single, Channel, Structure) with proper validation
- [ ] Section creation includes site-specific settings for multi-site installations
- [ ] UpdateSection tool preserves entry data during safe modifications
- [ ] DeleteSection tool provides comprehensive impact assessment before deletion
- [ ] CreateEntryType tool supports both section-specific and global entry types
- [ ] Entry type tools integrate seamlessly with existing field management system
- [ ] UpdateFieldLayout tool enables complete field organization within entry types
- [ ] All tools include control panel URLs for further configuration and review
- [ ] Comprehensive error handling provides clear feedback for validation failures
- [ ] Tools respect Craft's project config system for environment synchronization
- [ ] Section type changes include appropriate warnings about data implications
- [ ] Field layout modifications preserve existing content where possible
- [ ] All tools follow established MCP patterns with proper testing coverage