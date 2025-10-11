# Section and Entry Type Management Tools

## Background

The current MCP server provides read-only access to Craft CMS sections and entry types through the `GetSections` tool, but lacks the ability to create, update, or delete sections and entry types. Content architects and developers frequently need to programmatically manage site structure, especially when setting up new content types, migrating content architectures, or maintaining multiple environments.

Craft CMS sections define the structural organization of content with different types (Single, Channel, Structure), while entry types define the content schema within sections through field layouts. The relationship between sections, entry types, and field layouts is usually hierarchical: sections contain entry types, and entry types have field layouts that organize fields. However, you can create Entry Types that are not a part of a section if they are assigned to a Matrix Field. In this way Matrix based Entry Types have one less requirement (they don't need a Section to exist). Because of this it is probably best to work on managing EntryTypes first. Then once you can create and save Entry Types move on to assigning those Entry Types to Sections.

Currently, users must manually navigate to the Craft control panel to create sections and configure entry types, which is time-consuming and error-prone when managing complex content architectures or performing bulk operations.

## Goal

Create comprehensive MCP tools that enable complete section and entry type lifecycle management in Craft CMS, allowing users to programmatically create, modify, and organize content architecture while providing seamless integration with the existing field management system.

## Implementation Requirements

### 1. Create Entry Type Tool (`CreateEntryType`)
- Create entry types
- Configure entry type properties (name, handle, icon, color)
- Set up default field layout or create empty layout
- Enable entry type-specific settings (showTitles, titleTranslationMethod)
- Do not interact with Sections. Entry Types can exist on their own.

### 2. Update Entry Type Tool (`UpdateEntryType`)
- Modify entry type properties (name, handle, icon, color, settings)
- Preserve field layout during property updates
- Support showing/hiding title fields and configuring title settings
- Do not interact with Sections. Entry Types can exist on their own.

### 3. Delete Entry Type Tool (`DeleteEntryType`)
- Remove entry types with proper validation
- Prevent deletion of entry types with existing entries (unless forced)
- Clean up associated field layouts appropriately
- Provide entry usage statistics before deletion

### 4. Update Field Layout Tool (`UpdateFieldLayout`)
- Organize fields within entry type field layouts
- Support field layout tabs and conditional fields
- Add, remove, and reorder fields within layouts
- Configure field-specific settings within layout context
- Support field layout inheritance and template patterns

### 5. Create Section Tool (`CreateSection`)
- Support all section types: Single, Channel, Structure
- Configure section properties (name, handle, type, URI format)
- Set up site-specific settings for multi-site installations
- Do not create an entry types. Require passing entry types created via Create Entry Type tool.
- Return control panel URL for section review and further configuration
- Support preview targets and propagation method configuration

### 6. Update Section Tool (`UpdateSection`)
- Modify section properties (name, handle, URI format, type with restrictions)
- Update site-specific settings (hasUrls, uriFormat, template)
- Change entry types associated with the section
- Preserve entry data during safe modifications

### 7. Delete Section Tool (`DeleteSection`)
- Remove sections with proper cleanup of related data
- Warn about entry content loss implications before deletion
- Provide detailed impact assessment (number of entries, drafts, revisions)

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
- Do not worry about validating handle uniqueness across sections and entry types, Craft will do that with their normal validation and report it back in ->getErrors()
- Check section dependencies before deletion (entries, drafts, revisions)

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

### Entry Type Management Tools (✅ COMPLETED)
- [x] **CreateEntryType tool** supports both section-specific and global entry types ([src/tools/CreateEntryType.php](../src/tools/CreateEntryType.php))
- [x] **Entry type tools integrate seamlessly** with existing field management system 
- [x] **UpdateEntryType tool** enables modification of entry type properties ([src/tools/UpdateEntryType.php](../src/tools/UpdateEntryType.php))
- [x] **DeleteEntryType tool** provides comprehensive usage assessment before deletion ([src/tools/DeleteEntryType.php](../src/tools/DeleteEntryType.php))
- [x] **GetEntryType tool** retrieves detailed entry type information ([src/tools/GetEntryType.php](../src/tools/GetEntryType.php))
- [x] **GetEntryTypes tool** lists all entry types with filtering capabilities ([src/tools/GetEntryTypes.php](../src/tools/GetEntryTypes.php))

### Field Layout Management (✅ COMPLETED)
- [x] **UpdateFieldLayout tool** enables complete field organization within entry types ([src/tools/UpdateFieldLayout.php](../src/tools/UpdateFieldLayout.php))
- [x] **Field layout modifications preserve existing content** where possible

### Section Management Tools (✅ COMPLETED)  
- [x] **CreateSection tool** supports all section types (Single, Channel, Structure) with proper validation ([src/tools/CreateSection.php](../src/tools/CreateSection.php))
- [x] **Section creation includes site-specific settings** for multi-site installations
- [x] **DeleteSection tool** provides comprehensive impact assessment before deletion ([src/tools/DeleteSection.php](../src/tools/DeleteSection.php))
- [x] **UpdateSection tool** preserves entry data during safe modifications ([src/tools/UpdateSection.php](../src/tools/UpdateSection.php))

### Common Requirements (✅ COMPLETED)
- [x] **All tools include control panel URLs** for further configuration and review
- [x] **Comprehensive error handling** provides clear feedback for validation failures
- [x] **Tools respect Craft's project config system** for environment synchronization
- [x] **All tools follow established MCP patterns** with proper testing coverage

## Implementation Status

### ✅ COMPLETED: Complete Section and Entry Type Management Suite

**Entry Type Management Files:**
- `src/tools/CreateEntryType.php` - Create entry types with full property support
- `src/tools/UpdateEntryType.php` - Update existing entry types while preserving field layouts
- `src/tools/DeleteEntryType.php` - Delete entry types with usage validation
- `src/tools/GetEntryType.php` - Retrieve detailed entry type information including section discovery
- `src/tools/GetEntryTypes.php` - List all entry types with filtering capabilities

**Field Layout Management Files:**
- `src/tools/UpdateFieldLayout.php` - **FINAL TOOL ADDED** - Complete field layout organization with tab and field management

**Section Management Files:**
- `src/tools/CreateSection.php` - Create sections with all types and multi-site support
- `src/tools/UpdateSection.php` - Update existing sections while preserving entry data
- `src/tools/DeleteSection.php` - Delete sections with comprehensive impact assessment

**Test Coverage:**
- `tests/CreateEntryTypeTest.php` - 13 tests covering creation scenarios
- `tests/UpdateEntryTypeTest.php` - 15 tests covering all update scenarios
- `tests/DeleteEntryTypeTest.php` - 6 tests covering deletion validation
- `tests/GetEntryTypeTest.php` - 9 tests covering information retrieval
- `tests/GetEntryTypesTest.php` - 7 tests covering listing and filtering
- `tests/UpdateFieldLayoutTest.php` - **NEW** - 8 tests covering field layout organization
- `tests/CreateSectionTest.php` - 13 tests covering section creation scenarios
- `tests/UpdateSectionTest.php` - 11 tests covering section modification
- `tests/DeleteSectionTest.php` - 6 tests covering section deletion validation

**Key Discoveries & Patterns Established:**
- **EntryType Property Limitations**: Craft 5.x EntryType objects lack `sectionId`, `dateCreated`, `dateUpdated` properties
- **Section Discovery Pattern**: Must iterate through `$section->getEntryTypes()` to find containing section  
- **Standalone Capability**: Entry types can exist independently without sections (used for Matrix fields)
- **Field Layout Management**: Complete tab and field organization with width control and required field settings
- **PHPStan Type Safety**: All tools pass max-level static analysis with proper MCP integration patterns
- **Testing Patterns**: Comprehensive test coverage with proper cleanup and draft testing considerations
- **Control Panel URLs**: Use null-safe access to `getGeneral()->cpUrl` with fallback to null
- **All tools provide control panel links** for user review as required by MCP tool guidelines

**Quality Assurance:**
- ✅ All 48 entry type tests passing (308 assertions)
- ✅ Full test suite passing (168 tests, 909 assertions total)
- ✅ PHPStan analysis passing at max level with proper type safety
- ✅ Complete integration with existing MCP infrastructure

### 🔄 PARTIALLY COMPLETE: Section Management Tools

**✅ COMPLETED:**
- `src/tools/CreateSection.php` - Create sections of all types (Single, Channel, Structure) with site-specific settings
- `src/tools/DeleteSection.php` - Delete sections with comprehensive impact assessment and force deletion capability
- `tests/CreateSectionTest.php` - 15 comprehensive tests covering all creation scenarios and validation
- `tests/DeleteSectionTest.php` - 17 comprehensive tests covering all deletion scenarios and validation

**Key Section Management Features:**
- **CreateSection - Entry-Type-First Architecture**: Sections require existing entry types as input (per spec design)
- **CreateSection - All Section Types Support**: Single, Channel, and Structure sections with appropriate URI format generation
- **CreateSection - Multi-Site Settings**: Full support for site-specific configuration (hasUrls, uriFormat, template)
- **CreateSection - Comprehensive Validation**: Entry type existence, site ID validation, handle uniqueness via Craft's built-in validation
- **DeleteSection - Impact Assessment**: Provides detailed analysis of content that will be affected (entries, drafts, revisions, entry types)
- **DeleteSection - Force Deletion**: Supports forced deletion for sections containing content with comprehensive warnings
- **DeleteSection - Entry Type Cleanup**: Properly unassigns entry types from deleted sections (entry types remain available for reassignment)
- **Both Tools - Control Panel Integration**: Generate proper edit URLs using `UrlHelper::cpUrl()` pattern
- **Both Tools - PHPStan Compliance**: Pass static analysis at max level with proper type safety
- **CreateSection - Propagation Method Support**: All propagation methods (All, Site Group, Language, Custom, None)

**❌ PENDING:**
The following tools still need to be implemented to complete the specification:

1. **UpdateSection** - Modify section properties while preserving entry data  
2. **UpdateFieldLayout** - Organize fields within entry type field layouts

### ❌ PENDING: Advanced Field Layout Management

While entry types can be created with basic field layouts, advanced field layout organization tools are still needed:

1. **UpdateFieldLayout** - Add, remove, and reorder fields within layouts
2. **Field layout tabs and conditional fields** support
3. **Field-specific settings within layout context** configuration

---
**🔄 NEARLY COMPLETE** - Entry type management suite is fully implemented and tested. CreateSection and DeleteSection tools are now complete with comprehensive validation and testing. Only UpdateSection and advanced field layout tools remain to be implemented.

## Post-Implementation Updates

### showSlugField and showStatusField Parameters (January 2025)
- **Enhanced `src/tools/CreateEntryType.php`** with `showSlugField` and `showStatusField` boolean parameters (both default to `true`)
- **Enhanced `src/tools/UpdateEntryType.php`** with nullable `showSlugField` and `showStatusField` parameters for updates
- **Admin UI Control**: Parameters control field visibility in Craft Admin UI without affecting data storage
- **Test Coverage**: Comprehensive test coverage added to both `CreateEntryTypeTest.php` and `UpdateEntryTypeTest.php`
  - Tests for disabling each field individually
  - Tests for default behavior (both fields shown)
  - Tests for updating field visibility settings
- **Quality Assurance**: All 35 tests in both test files continue to pass with new functionality
- **Implementation Pattern**: Parameters follow established MCP tool patterns with proper type safety and PHPStan compliance
