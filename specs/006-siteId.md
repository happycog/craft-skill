# Add siteId Support to CreateEntry Tool

## Background

Craft CMS supports multi-site installations where content can be created for different sites (locales, regions, brands, etc.). The CreateDraft tool already has comprehensive siteId support allowing drafts to be created for specific sites, but the CreateEntry tool lacks this capability.

Currently, entries created through the CreateEntry tool are always created for the primary site, limiting the tool's usefulness in multi-site installations where content needs to be created for specific sites.

## Goal

Add siteId parameter support to the CreateEntry tool, bringing it to feature parity with CreateDraft and enabling AI assistants to create entries for any site in a multi-site Craft installation.

## Implementation Requirements

### 1. Add siteId Parameter to CreateEntry Tool ✅
- ✅ Add optional `siteId` parameter to CreateEntry tool method signature
- ✅ Parameter should default to primary site if not provided (maintaining backward compatibility)
- ✅ Include proper parameter documentation and schema annotation
- ✅ Follow the same pattern established in CreateDraft tool

### 2. Modify UpsertEntry Action ✅
- ✅ Add `siteId` parameter to UpsertEntry action's `__invoke` method
- ✅ Set `$entry->siteId` property when creating new entries
- ✅ Maintain existing behavior for entry updates (don't change siteId of existing entries)

### 3. Add Site Validation ✅
- ✅ Validate that the provided siteId exists before creating the entry
- ✅ Throw descriptive exception if invalid siteId is provided
- ✅ Use same validation pattern as CreateDraft tool

### 4. Update Tool Documentation ✅
- ✅ Update CreateEntry tool description to mention siteId support
- ✅ Include examples of siteId usage in tool documentation
- ✅ Reference GetSites tool for discovering valid siteId values

## Technical Implementation Notes

### Site Handling
- ✅ Use `Craft::$app->getSites()->getPrimarySite()->id` for default siteId
- ✅ Use `Craft::$app->getSites()->getSiteById($siteId)` for validation
- ✅ Set `$entry->siteId` property before calling `saveElement()`

### Backward Compatibility
- ✅ Make siteId parameter optional with null default
- ✅ Existing API calls without siteId should continue working unchanged
- ✅ Default behavior should remain creating entries for primary site

### Error Handling
- ✅ Throw `InvalidArgumentException` for invalid siteId values
- ✅ Include descriptive error messages that help users understand the issue
- ✅ Follow same error handling pattern as CreateDraft tool

## Non-Requirements (Future Considerations)

- Updating siteId of existing entries (should remain in UpdateEntry tool scope)
- Bulk site operations or cross-site content duplication
- Automatic site detection based on content language
- Site-specific field validation rules

## Acceptance Criteria

1. ✅ CreateEntry tool accepts optional `siteId` parameter
2. ✅ When siteId is provided, entry is created for that specific site
3. ✅ When siteId is omitted, entry is created for primary site (existing behavior)
4. ✅ Invalid siteId values result in clear error messages
5. ✅ Tool documentation accurately describes siteId functionality
6. ✅ Implementation follows same patterns as CreateDraft tool
7. ✅ All existing tests continue to pass
8. ✅ New tests cover siteId functionality including validation and error cases

## Implementation Summary

**Completed:** September 17, 2025

### Files Modified:
- `src/tools/CreateEntry.php`: Added siteId parameter, validation, and documentation
- `src/actions/UpsertEntry.php`: Added siteId parameter and logic to set siteId for new entries
- `tests/CreateEntryTest.php`: Added comprehensive tests for siteId functionality

### Key Changes:
1. **CreateEntry Tool**: Added optional `siteId` parameter with proper schema annotation and validation
2. **UpsertEntry Action**: Modified to accept and handle siteId parameter for new entries only
3. **Site Validation**: Added validation using `Craft::$app->getSites()->getSiteById()` with descriptive error messages
4. **Documentation**: Updated tool description to include siteId usage and reference to GetSites tool
5. **Tests**: Added tests for primary site default, explicit siteId, and invalid siteId error handling

### Test Results:
- All existing tests pass, ensuring backward compatibility
- New tests verify siteId functionality works correctly
- Error handling tested with invalid siteId values

The implementation successfully brings CreateEntry to feature parity with CreateDraft regarding site support while maintaining full backward compatibility.
