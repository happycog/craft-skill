create a new tool in `src/tools` following the conventions of the existing tools. The new tool should be called
`DeleteEntry` and should interact with the Craft SDK to delete an entry from the database. It should offer an optional
boolean input that "Permanently deletes" the entry. The default Craft behavior is a soft delete, we'll keep that in our
tool. The boolean will allow the entry to be permanently deleted if set to true.

## Implementation Status: COMPLETED ✅

### What was implemented:

1. **DeleteEntry Tool** (`src/tools/DeleteEntry.php`)
   - Implements #[McpTool] attribute with name 'delete_entry'
   - Accepts `entryId` (number) and optional `permanentlyDelete` (boolean, default false)
   - Uses Craft's ElementsService for both soft and hard deletion
   - Returns comprehensive entry information including deletion status
   - Follows established conventions from other tools (CreateEntry, UpdateEntry, GetEntry)

2. **Comprehensive Test Suite** (`tests/DeleteEntryTest.php`)
   - 8 test cases covering all major functionality
   - Soft delete (default behavior) validation
   - Permanent delete functionality
   - Proper response format verification
   - Error handling for non-existent entries
   - Cross-section deletion testing
   - Custom field handling
   - Date format handling

### Key Features:
- **Soft Delete (Default)**: Entries are marked as trashed but remain in database, can be restored
- **Hard Delete (Optional)**: Permanently removes entries from database when `permanentlyDelete: true`
- **Comprehensive Response**: Returns entry ID, title, slug, section info, post date, and deletion status
- **Error Handling**: Throws appropriate exceptions for missing entries or deletion failures
- **Validation**: Ensures only Entry elements are processed
- **Auto-Discovery**: Tool is automatically registered via Plugin.php's discover() method

### Tool Signature:
```php
public function delete(
    int $entryId,
    bool $permanentlyDelete = false,
): array
```

### Response Format:
```php
[
    'entryId' => int,
    'title' => string,
    'slug' => string,
    'sectionId' => int,
    'sectionName' => string,
    'postDate' => string|null,
    'deletedPermanently' => bool,
]
```

### Architecture Notes:
- **Auto-Registration**: No manual registration needed - uses existing discover() pattern
- **Convention Compliance**: Follows same patterns as CreateEntry/UpdateEntry/GetEntry
- **Error Safety**: Validates entry existence before attempting deletion
- **Craft Integration**: Uses Craft's ElementsService for proper deletion handling
- **Schema Attributes**: Proper PhpMcp\Server\Attributes\Schema documentation

### Testing Results:
- ✅ All 8 tests passing (35 assertions)
- ✅ Soft delete functionality verified
- ✅ Hard delete functionality verified
- ✅ Error handling validated
- ✅ Response format confirmed
- ✅ Cross-section compatibility tested

### Tool Discovery:
The tool is automatically discovered and registered through the existing Plugin.php discover() mechanism that scans the `src/tools` directory. No additional registration code was needed.

The DeleteEntry tool successfully completes the CRUD operations suite, joining CreateEntry, GetEntry, UpdateEntry, GetSections, GetFields, and SearchContent as available MCP tools.
