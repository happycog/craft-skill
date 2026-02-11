# AGENTS.md - Project Documentation for LLMs

## Project Overview

This is a standalone CLI tool that provides programmatic access to Craft CMS content management capabilities. The tool is distributed as a self-contained PHAR executable that bootstraps Craft CMS and exposes all CMS functionality through a clean command-line interface designed specifically for AI agents and automation workflows.

## Tech Stack

- **Backend**: PHP 8.1+ with Craft CMS 5.x framework
- **Validation**: CuyZ/Valinor ^2.2 for parameter validation and mapping
- **Distribution**: PHAR (PHP Archive) - self-contained executable
- **CLI Framework**: Custom argument parser with support for positional args, flags, and JSON data
- **Testing**: Pest PHP testing framework with craft-pest-core
- **Package Management**:
  - PHP: Composer (development only)
- **Build Tool**: PHAR compiler for creating standalone executable

## Project Structure

```
/
├── bin/
│   └── agent-craft                  # CLI entrypoint script
├── src/
│   ├── actions/
│   │   ├── UpsertEntry.php          # Entry creation/update action
│   │   ├── EntryTypeFormatter.php   # Entry type formatting
│   │   └── FieldFormatter.php       # Field formatting
│   ├── attributes/
│   │   ├── BindToContainer.php      # DI container binding attribute
│   │   ├── Init.php                 # Initialization attribute
│   │   └── RegisterListener.php     # Event listener registration
│   ├── base/
│   │   └── Plugin.php               # Base plugin class with DI
│   ├── cli/
│   │   ├── ArgumentParser.php       # CLI argument parser
│   │   └── CommandRouter.php        # Command routing
│   ├── controllers/
│   │   ├── Controller.php           # Base controller with Valinor validation
│   │   ├── EntriesController.php    # Entry CRUD endpoints
│   │   ├── SectionsController.php   # Section management endpoints
│   │   ├── EntryTypesController.php # Entry type management endpoints
│   │   ├── FieldsController.php     # Field management endpoints
│   │   ├── FieldLayoutsController.php # Field layout endpoints
│   │   ├── DraftsController.php     # Draft management endpoints
│   │   └── SitesController.php      # Site information endpoints
│   ├── tools/                       # Business logic implementations
│   │   ├── CreateEntry.php          # Content creation logic
│   │   ├── UpdateEntry.php          # Content modification logic
│   │   ├── DeleteEntry.php          # Content deletion logic
│   │   ├── GetEntry.php             # Content retrieval logic
│   │   ├── SearchContent.php        # Content search logic
│   │   ├── CreateDraft.php          # Draft creation logic
│   │   ├── UpdateDraft.php          # Draft modification logic
│   │   └── ...                      # Additional tool implementations
│   ├── exceptions/
│   │   └── ModelSaveException.php   # Craft model save error handling
│   ├── helpers/
│   │   └── functions.php            # Laravel-style helper functions
│   └── Plugin.php                   # Main plugin class
├── tests/                           # Pest test suite
├── stubs/project/                   # Craft project configuration
├── specs/                           # Implementation specifications
├── .phpstorm.meta.php/              # IDE and static analysis type hints
├── composer.json                    # PHP dependencies
└── phpunit.xml                      # Test configuration
```

## Key Configuration Files

### 1. `composer.json`
- Craft CMS plugin type with proper autoloading
- cuyz/valinor dependency for request validation
- craft-pest-core for testing framework
- PHPStan for static analysis
- `"bin": ["bin/agent-craft"]` for CLI script distribution

### 2. `src/Plugin.php`
- Main plugin entry point with dependency injection setup
- HTTP route registration for all API endpoints
- Controller mapping and URL rules configuration

### 3. `phpunit.xml`
- Pest testing framework configuration
- Test directory scanning for `*Test.php` files
- Source code coverage for `src/` directory

### 4. `tests/Pest.php`
- Pest configuration with Craft-specific test case
- RefreshesDatabase trait for clean test isolation
- Custom expectations and helper functions

### 5. `.phpstorm.meta.php/`
- PhpStorm metadata for improved IDE type hints and autocomplete
- PHPStan stub files for proper static analysis type resolution
- Container generics ensure `$container->get(ClassName::class)` returns correct types

### 6. `phpstan.neon`
- PHPStan configuration at maximum strictness level (`level: max`)
- Includes official Craft CMS PHPStan configuration (`craftcms/phpstan:dev-main`)
- Custom stub files for enhanced type safety
- Memory limits configured for Craft CMS analysis

## Development Commands

### PHP/Backend
```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Run tests with verbose output
./vendor/bin/pest -vvv

# Run specific test file
./vendor/bin/pest tests/CreateEntryTest.php

# Static analysis (max level with Craft CMS integration)
./vendor/bin/phpstan analyse

# Static analysis with specific file
./vendor/bin/phpstan analyse src/tools/ExampleTool.php

# Generate PHPStan baseline (if needed)
./vendor/bin/phpstan analyse --generate-baseline

# Build the PHAR (requires phar.readonly disabled)
php -d phar.readonly=0 bin/build-phar.php
```

### CLI Testing
```bash
# IMPORTANT: The CLI tool works from any directory
# Use --path to specify the Craft installation location

# Test basic functionality from plugin directory
./bin/agent-craft sections/list

# Test with custom Craft path
./bin/agent-craft sections/list --path=/path/to/craft

# Test entry creation with inline JSON
./bin/agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --attributeAndFieldData='{"title":"Test Entry","body":"<p>Content</p>"}'

# Test entry creation with JSON file (recommended for complex data)
# Create data.json first:
# {
#   "title": "My Entry",
#   "body": "<p>This is the body content</p>",
#   "customField": "value"
# }
./bin/agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --attributeAndFieldData=@data.json

# Test positional arguments
./bin/agent-craft entries/get 123

# Test verbose output
./bin/agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --attributeAndFieldData=@entry.json \
  -vvv

# Using PHAR distribution
./agent-craft.phar sections/list
```

**IMPORTANT - CLI Data Input Patterns:**
- **Inline JSON** (simple): `--attributeAndFieldData='{"title":"Test"}'`
- **File Reference** (recommended): `--attributeAndFieldData=@data.json`
- **Why file references?** Avoids shell escaping issues with special characters like `!`, `$`, `[]`, etc.
- **File path**: Relative to current working directory (not the PHAR location)

### PHPStan Configuration
The project uses PHPStan at **level max** (strictest possible) with official Craft CMS integration:

```neon
# phpstan.neon
includes:
    - vendor/craftcms/phpstan/phpstan.neon

parameters:
    level: max
    paths:
        - src
```

**Key Points:**
- Uses `craftcms/phpstan:dev-main` package for proper Craft CMS type recognition
- All `Craft::$app` and `Yii::$app` references are properly typed
- Level max requires explicit array type annotations
- Memory limit increased to 1GB for Craft CMS analysis
- Custom stub files in `.phpstorm.meta.php/` for enhanced IDE support

## Development Patterns

### 1. Creating New CLI Commands
Create tool classes in `src/tools/` that implement the business logic:

**Tool Implementation with Dependency Injection:**

```php
// src/tools/ExampleTool.php
namespace happycog\craftmcp\tools;

class ExampleTool
{
    // CRITICAL: Use constructor injection instead of Craft::$container->get()
    public function __construct(
        protected ExampleService $exampleService,
        protected AnotherDependency $anotherDependency,
    ) {
    }

    /**
     * Example tool description that supports multiple lines.
     *
     * After performing action always link the user back to the relevant page in the Craft
     * control panel so they can review the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(
        string $parameter,
        ?int $optionalNumber = null,
        array $data = [],
    ): array {
        // Use injected dependencies instead of manual container access
        $result = ($this->exampleService)($parameter, $data);

        return [
            'success' => true,
            'result' => 'Tool executed successfully',
            'parameter' => $parameter,
        ];
    }
}
```

**CLI Invocation:**

The CLI argument parser automatically maps command-line arguments to tool method parameters:

```bash
# Simple flags map directly to parameters
./agent-craft.phar examples/create --parameter="test value"

# Positional arguments for IDs
./agent-craft.phar examples/get 123

# Bracket notation for structured data (recommended)
./agent-craft.phar examples/create \
  --parameter="test" \
  --data[key]="value" \
  --data[nested][foo]="bar" \
  --data[items]=1,2,3

# Auto-indexed arrays
./agent-craft.phar examples/create \
  --parameter="test" \
  --data[items][]=1 \
  --data[items][]=2 \
  --data[items][]=3

# JSON strings for very complex nested structures (fallback)
./agent-craft.phar examples/create \
  --parameter="test" \
  --data='{"key":"value","nested":{"foo":"bar"}}'
```

### 2. CLI Argument Parsing
The CLI framework provides automatic argument parsing with Valinor validation:

- **Positional Arguments**: Map to method parameters in order (e.g., `entries/get 123` → `get(int $id)`)
- **Flag Arguments**: Map to named parameters (e.g., `--title="Test"` → `create(string $title)`)
- **Bracket Notation**: Parse nested structures using query string style (e.g., `--fields[body]="text"` → `['fields' => ['body' => 'text']]`)
- **Array Syntax**: Support both comma-separated (`--ids=1,2,3`) and auto-indexed (`--items[]=1 --items[]=2`)
- **JSON Fallback**: Complex nested data can use JSON strings (e.g., `--data='{"complex":"structure"}'`)
- **Type Validation**: Valinor validates and type-casts all parameters automatically
- **Error Handling**: Invalid arguments return exit code 2 with error message to stderr

### 3. Testing
Use Pest with direct tool invocation patterns:

```php
// tests/ExampleTest.php
test('tool creates resource successfully', function () {
    $tool = \Craft::$container->get(ExampleTool::class);
    
    $result = $tool->create(
        parameter: 'test value',
        data: ['key' => 'value']
    );

    expect($result['success'])->toBeTrue();
    expect($result['parameter'])->toBe('test value');
});

// Testing with Craft elements
test('tool retrieves entry', function () {
    $entry = Entry::factory()->create();
    $tool = \Craft::$container->get(GetEntry::class);
    
    $result = $tool->get(id: $entry->id);
    
    expect($result)->toHaveKey('id');
    expect($result['id'])->toBe($entry->id);
});
```

## Current State

### ✅ Completed Setup
- [x] RESTful HTTP API with Valinor validation
- [x] Controller-based architecture with automatic request mapping
- [x] Complete CRUD operations for Craft entries
- [x] Section, entry type, and field management endpoints
- [x] Field layout configuration endpoints
- [x] Content search capabilities
- [x] Draft support with create, update, and apply operations
- [x] Site information endpoints
- [x] Asset management with upload, update, and delete operations
- [x] Comprehensive test suite with Pest
- [x] CLI argument parser with positional args and flags
- [x] JSON parameter parsing for complex data
- [x] Craft bootstrap logic from CLI context
- [x] Path detection and --path override flag
- [x] Verbose output flags (-v, -vv, -vvv)
- [x] Error handling with appropriate exit codes
- [x] Command routing for all existing tools
- [x] CLI integration test suite (83 tests)
- [x] PHAR build script and distribution (`agent-craft.phar`)

### 🔜 Ready for Development
- [ ] Enhanced error handling and validation
- [ ] Performance optimization for large content sets
- [ ] Extended field type support

## Important Notes for Future Agents

### Skills Documentation Maintenance

**CRITICAL**: The `SKILLS.md` and `SKILLS/*.md` files must be kept in sync with the actual tool implementations in `src/tools/`.

#### When Adding New Tools:
1. **Create the tool file** in `src/tools/ToolName.php`
2. **Create matching documentation** in `SKILLS/tool_name.md` (use snake_case filename)
3. **Update SKILLS.md** to list the new tool in the appropriate section
4. **Follow the documentation template**:
   - Tool name as heading
   - Brief description
   - API route (if applicable)
   - Parameters (required and optional, with types)
   - Return value description
   - Example usage with JSON
   - Notes section with important considerations

#### When Modifying Tools:
1. **Update the tool file** in `src/tools/`
2. **Update corresponding documentation** in `SKILLS/tool_name.md`
3. **Update SKILLS.md** if the tool's purpose or API changes
4. **Test the tool** to ensure examples in documentation are accurate

#### When Deprecating/Removing Tools:
1. **Mark as deprecated** in `SKILLS/tool_name.md` with migration guide
2. **Update SKILLS.md** to remove or mark as deprecated
3. **Keep deprecated docs** for 1-2 releases to help users migrate
4. **Eventually remove** both tool file and documentation when fully obsolete

#### Documentation Standards:
- **Filenames**: Use snake_case (e.g., `add_field_to_field_layout.md`)
- **Consistency**: Match parameter names and types exactly with tool implementation
- **Examples**: Provide realistic, copy-pasteable JSON examples
- **Cross-references**: Link related tools in "See Also" sections
- **Clarity**: Write for users who may not be Craft CMS experts

#### Verification Commands:
```bash
# List all tools
ls -1 src/tools/*.php | xargs -n1 basename | sed 's/.php$//' | sort

# List all documented skills
ls -1 SKILLS/*.md | xargs -n1 basename | sed 's/.md$//' | sort

# Find undocumented tools (tools without matching .md files)
# Compare the two lists above - each tool should have a corresponding doc
```

### Base URL Configuration for HTTP API
- **DEPRECATED**: The HTTP API is being replaced by a CLI interface
- **Legacy Information**: Previously used `PRIMARY_SITE_URL` environment variable and configurable API prefix
- **Migration Path**: All HTTP functionality will be accessible via CLI commands in the PHAR executable

### CLI Tool Architecture (Replacing HTTP API)
- **PHAR Distribution**: Self-contained executable with all dependencies bundled
- **Craft Bootstrap**: Tool bootstraps Craft CMS internally from PHAR context
- **Argument Parsing**: Custom CLI parser with support for positional args, flags, and JSON data
- **Command Format**: `agent-craft <tool/action> [positional-args] [--flags]`
- **Validation**: Uses Valinor library for automatic type checking and parameter mapping
- **Tool Layer**: Business logic remains in `src/tools/` directory (unchanged from HTTP API)
- **Dependency Injection**: Tools use constructor injection for clean architecture
- **Available Commands**:
  - `entries/create` - Create entry
  - `entries/get <id>` - Get entry
  - `entries/update <id>` - Update entry
  - `entries/delete <id>` - Delete entry
  - `entries/search` - Search entries
  - `sections/create` - Create section
  - `sections/list` - List sections
  - `sections/update <id>` - Update section
  - `sections/delete <id>` - Delete section
  - `entry-types/create` - Create entry type
  - `entry-types/list` - List entry types
  - `entry-types/update <id>` - Update entry type
  - `entry-types/delete <id>` - Delete entry type
  - `fields/create` - Create field
  - `fields/list` - List fields
  - `fields/types` - List field types
  - `fields/update <id>` - Update field
  - `fields/delete <id>` - Delete field
  - `drafts/create` - Create draft
  - `drafts/update <id>` - Update draft
  - `drafts/apply <id>` - Apply draft
  - `field-layouts/create` - Create field layout
  - `field-layouts/get` - Get field layout
  - `field-layouts/update <id>` - Update field layout
  - `sites/list` - List sites
  - `assets/create` - Create asset
  - `assets/update <id>` - Update asset
  - `assets/delete <id>` - Delete asset
  - `volumes/list` - List volumes

### CLI Architecture Implementation (Completed)

The CLI framework is fully implemented with three core components:

#### 1. ArgumentParser (`src/cli/ArgumentParser.php`)
Lightweight argument parser optimized for AI agents:

- **Positional Arguments**: First arg is command, rest are positional params
- **Flag Arguments**: `--key=value` syntax
- **Bracket Notation**: `--fields[body]=text` → `['fields' => ['body' => 'text']]`
- **Comma-Separated Arrays**: `--ids=1,2,3` → `['ids' => [1, 2, 3]]`
- **Auto-Indexed Arrays**: `--items[]=1 --items[]=2` → `['items' => [1, 2]]`
- **JSON Parsing**: Detects and parses JSON strings starting with `{` or `[`
- **Type Auto-Detection**: Converts "true"/"false" to bool, numbers to int
- **Verbosity Extraction**: `-v`, `-vv`, `-vvv` mapped to levels 0-3
- **Path Override**: `--path=/custom/path` extracted separately

**Return Structure**:
```php
[
    'command' => 'entries/create',
    'positional' => [123],
    'flags' => ['title' => 'Test', 'fields' => ['body' => 'text']],
    'verbosity' => 2,
    'path' => '/path/to/craft'
]
```

**Known Limitations**:
- Auto-indexed arrays (`--items[]=1 --items[]=2`) only keep last value due to `parse_str()` behavior
- Bracket notation with comma-separated values produces string "Array" - use simple flags instead

#### 2. CommandRouter (`src/cli/CommandRouter.php`)
Routes CLI commands to tool methods:

- **Command Map**: 27 commands mapped to tool classes and methods
- **Valinor Integration**: Uses `ArgumentsMapper` for parameter validation
- **Positional + Flag Merging**: Combines positional args with flags using reflection
- **DI Container**: Gets tool instances via `Craft::$container->get()`
- **Error Handling**: Throws `InvalidArgumentException` for unknown commands

**Adding New Commands**:
Add entries to the `COMMAND_MAP` constant:
```php
private const COMMAND_MAP = [
    'entries/create' => [CreateEntry::class, 'create'],
    'your-command' => [YourTool::class, 'methodName'],
];
```

#### 3. CLI Entrypoint (`bin/agent-craft`)
Main executable script that bootstraps Craft and executes commands:

- **Shebang**: `#!/usr/bin/env php` for direct execution
- **Craft Detection**: Looks for `vendor/craftcms/cms` at `--path` or current directory
- **Bootstrap Process**: Loads autoloader, defines constants, loads .env, bootstraps console
- **Webroot Alias Fix**: Mocks `$_SERVER['SCRIPT_FILENAME']` to point to the target Craft installation's `craft` script, ensuring `@webroot` alias resolves correctly from the target installation (not PHAR location)
- **Argument Flow**: Parse → Route → Execute → Output
- **Exit Codes**:
  - `0`: Success
  - `1`: General error (tool execution failed)
  - `2`: Invalid arguments (validation failed, unknown command)
  - `3`: Craft not found or bootstrap failed
- **Output Format**:
  - Success: Pretty-printed JSON to stdout
  - Errors: JSON error messages to stderr
- **Verbosity Levels**:
  - `-v`: Exception message
  - `-vv`: + Stack trace
  - `-vvv`: + File, line, and code details

**Development Mode**: Script detects if running from plugin directory and adjusts paths accordingly.

**Script Filename Mocking**: When bootstrapping Craft from a PHAR or non-standard location, the script sets `$_SERVER['SCRIPT_FILENAME']` to the target Craft installation's `craft` executable (if it exists). This allows Craft's console Request class to correctly auto-detect the webroot by looking for common web directories (`web`, `public`, `public_html`, `html`) relative to the `craft` script location, rather than using the PHAR file location.

#### Testing
Comprehensive test suite with 83 tests:
- `tests/ArgumentParserTest.php`: 53 tests (122 assertions)
- `tests/CommandRouterTest.php`: 5 tests
- `tests/CliIntegrationTest.php`: 25 tests (130 assertions)

All tests use Pest PHP and follow existing project patterns.

#### Usage Examples
See README.md for detailed usage examples. Key patterns:

```bash
# Simple command
./bin/agent-craft sections/list

# Positional arguments
./bin/agent-craft entries/get 123

# Inline JSON for simple data
./bin/agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --attributeAndFieldData='{"title":"Test","body":"Content"}'

# File reference for complex data (recommended)
./bin/agent-craft entries/create \
  --sectionId=1 \
  --entryTypeId=2 \
  --attributeAndFieldData=@data.json

# Custom path
./bin/agent-craft --path=/path/to/craft sections/list

# Verbose errors
./bin/agent-craft invalid/command -vvv
```

### Legacy HTTP API (Deprecated)
- **Status**: Being replaced by CLI interface
- **Controllers**: Legacy controller classes remain in `src/controllers/` for reference
- **Route Registration**: HTTP route registration in Plugin.php deprecated
- **Migration**: All HTTP endpoints have equivalent CLI commands

### CLI Implementation Notes
- **Command Mapping**: CommandRouter requires manual mapping for new commands in the `COMMAND_MAP` constant
- **Exit Codes**: Standardized across all commands (0=success, 1=general error, 2=invalid args, 3=bootstrap failed)
- **Development Mode**: Script auto-detects when running from plugin directory and adjusts paths for bootstrapping Craft
- **Path Resolution**: Supports both `--path` flag and working directory detection for locating Craft installation
- **Verbosity Levels**: Three levels of error detail (-v, -vv, -vvv) for debugging and troubleshooting
- **Testing Pattern**: Integration tests run actual CLI commands via `exec()` to verify end-to-end behavior

### Craft 5.x Specific Considerations
- **Draft Properties**: Always use `draftName`, `draftNotes`, `isProvisionalDraft` - these are the correct Craft 5.x property names
- **Deprecated Properties**: Never use `revisionName` or `revisionNotes` - these are from older Craft versions
- **API Discovery**: When working with undocumented Craft features, examine the core Element classes and test property access
- **Control Panel URLs**: Use `Craft::$app->getConfig()->general->cpUrl` for generating edit URLs
- **Element Queries**: Draft elements have special query behaviors - they reference canonical entries via `canonicalId`
- **EntryType Properties**: EntryType objects in Craft 5.x DO NOT have `sectionId`, `dateCreated`, or `dateUpdated` properties
- **EntryType-Section Relationship**: To find which section contains an entry type, iterate through all sections and check their `getEntryTypes()` method
- **Standalone Entry Types**: Entry types can exist independently without being associated with a section (useful for Matrix fields)

### Valinor Integration
- **Automatic Validation**: Request parameters are automatically validated and type-cast using Valinor
- **Type Safety**: Parameter types from tool methods are enforced at runtime
- **Error Handling**: Invalid requests return 400 status with detailed error messages
- **Permissive Types**: Builder configured with `allowPermissiveTypes()` and `allowScalarValueCasting()`
- **Parameter Sources**:
  - POST/PUT: Body parameters via `$this->request->getBodyParams()`
  - GET: Query parameters via `$this->request->getQueryParams()`
  - Path: Merged into parameters via controller action arguments

### Craft CMS Integration
- Plugin extends craft\base\Plugin with custom DI container
- Uses #[BindToContainer] and #[RegisterListener] attributes for clean architecture
- Tool implementations work directly with Craft's element system
- Respects Craft's permissions and user context when available
- Controllers use `allowAnonymous = self::ALLOW_ANONYMOUS_LIVE` for API access
- CSRF validation disabled for JSON API compatibility

### Dependency Injection Pattern (Added after entry-types branch migration)
- **CRITICAL**: All tools in `src/tools/` directory MUST use constructor injection instead of manual container access
- **Container Access Prohibition**: NEVER use `Craft::$container->get()` or `$container->get()` within tool classes
- **Constructor Injection Pattern**:
  ```php
  class ExampleTool
  {
      public function __construct(
          protected ExampleService $exampleService,
          protected AnotherDependency $anotherDependency,
      ) {
      }

      public function toolMethod(): array
      {
          // CORRECT: Use injected dependencies
          $result = ($this->exampleService)($parameter);

          // INCORRECT: Manual container access (prohibited)
          // $service = Craft::$container->get(ExampleService::class);
      }
  }
  ```
- **Testing Pattern**: Tests should use `Craft::$container->get(ToolClass::class)` to get properly injected instances
- **Benefits**: Better type safety, cleaner architecture, easier testing, proper dependency management
- **Enforcement**: Architecture tests ensure compliance with this pattern across the codebase

### Draft System Implementation (Added in 005-add-draft-support.md)
- **CRITICAL**: Craft 5.x uses specific property names for draft metadata
- Use `$draft->draftName`, `$draft->draftNotes`, `$draft->isProvisionalDraft` (NOT `revisionName`/`revisionNotes`)
- Draft creation: `Craft::$app->getDrafts()->createDraft($canonicalEntry, $creatorId, $name, $notes, $attributes)`
- Control panel URLs: `Craft::$app->getConfig()->general->cpUrl . '/entries/' . $entry->id`
- **Testing Challenge**: RefreshesDatabase trait rolls back transactions, preventing database verification in tests
- **Solution**: Test return values and tool execution rather than database persistence in test environment

### Entry Type Management (Added in 010-section-entry-type-management.md)
- **CRITICAL**: EntryType objects in Craft 5.x DO NOT have `sectionId` property
- **Section Discovery**: To find which section contains an entry type, use this pattern:
  ```php
  $section = null;
  $sections = $entriesService->getAllSections();
  foreach ($sections as $sectionCandidate) {
      foreach ($sectionCandidate->getEntryTypes() as $sectionEntryType) {
          if ($sectionEntryType->id === $entryType->id) {
              $section = $sectionCandidate;
              break 2; // Break out of both loops
          }
      }
  }
  ```
- **Missing Properties**: EntryType objects also lack `dateCreated` and `dateUpdated` properties
- **Standalone Entry Types**: Entry types can exist without being associated with sections (commonly used for Matrix fields)
- **Control Panel URLs**: Section-dependent edit URLs should be null when entry type isn't associated with a section
- **Testing Pattern**: When testing tools that work with entry types created via CreateEntryType, expect section to be null initially

### Entry Type Usage Detection (Added in this session)
- **Purpose**: The `EntryTypeFormatter.php` now includes a `usedBy` key that shows which sections and Matrix fields reference an entry type
- **Implementation**: Uses `findEntryTypeUsage()` method to discover relationships through Craft's APIs
- **Section Discovery Pattern**:
  ```php
  $sections = $entriesService->getAllSections();
  foreach ($sections as $section) {
      foreach ($section->getEntryTypes() as $sectionEntryType) {
          if ($sectionEntryType->id === $entryType->id) {
              // Entry type is used by this section
          }
      }
  }
  ```
- **Matrix Field Discovery Pattern**:
  ```php
  $allFields = $fieldsService->getAllFields('global');
  foreach ($allFields as $field) {
      if ($field instanceof Matrix) {
          foreach ($field->getEntryTypes() as $blockType) {
              if ($blockType->id === $entryType->id) {
                  // Entry type is used as a block type in this Matrix field
              }
          }
      }
  }
  ```
- **Return Structure**: The `usedBy` key contains arrays for `sections` and `matrixFields`, each with id, name, handle, and type information
- **Performance**: Usage detection runs on each formatter call - consider caching for high-volume scenarios
- **Testing**: Use `EntryTypeFormatterTest.php` patterns for testing usage detection functionality
- **CRITICAL**: Craft 5.x uses specific property names for draft metadata
- Use `$draft->draftName`, `$draft->draftNotes`, `$draft->isProvisionalDraft` (NOT `revisionName`/`revisionNotes`)
- Draft creation: `Craft::$app->getDrafts()->createDraft($canonicalEntry, $creatorId, $name, $notes, $attributes)`
- Control panel URLs: `Craft::$app->getConfig()->general->cpUrl . '/entries/' . $entry->id`
- **Testing Challenge**: RefreshesDatabase trait rolls back transactions, preventing database verification in tests
- **Solution**: Test return values and tool execution rather than database persistence in test environment

### Testing Framework
- Uses Pest PHP with craft-pest-core for Craft-specific testing
- Complete test suite covering all HTTP endpoints and business logic
- RefreshesDatabase trait ensures clean test isolation
- Tests cover both unit functionality and HTTP endpoint behavior
- **Note**: Some tests may emit warnings due to Craft/Pest framework incompatibilities - this is expected and does not indicate test failure
- Run tests with `./vendor/bin/pest` - all tests should pass despite any warnings
- **Draft Testing Pattern**: When testing draft operations, focus on return value validation rather than database queries due to transaction rollbacks in test environment
- **Property Access**: Use correct Craft 5.x property names in tests (`draftName`, `draftNotes`, `isProvisionalDraft`)

### Error Handling
- Tools should return arrays with error information on failures
- Controllers automatically handle validation errors with 400 status codes
- Use ModelSaveException for Craft model save/delete failures

### ModelSaveException Pattern
- **PREFERRED**: Use `throw_unless` helper with ModelSaveException for all Craft model save/delete operations:
  ```php
  // Import ModelSaveException in tool files
  use happycog\craftmcp\exceptions\ModelSaveException;

  // PREFERRED: Concise throw_unless pattern
  throw_unless($entriesService->saveEntryType($entryType), ModelSaveException::class, $entryType);
  throw_unless($fieldsService->saveField($field), ModelSaveException::class, $field);
  throw_unless($sectionsService->deleteSection($section), ModelSaveException::class, $section);

  // ANTI-PATTERN: Verbose if/throw blocks (avoid these)
  if (!$entriesService->saveEntryType($entryType)) {
      throw new ModelSaveException($entryType);
  }
  ```
- **Automatic Context Generation**: ModelSaveException automatically generates context messages from model class names (e.g., "Failed to save entry type", "Failed to save field")
- **Consistent Error Handling**: All save/delete operations should use this pattern for consistent error messages across the API endpoints
- **Type Safety**: Pattern maintains PHPStan level max compliance with proper type checking

### Helper Functions
- **Laravel-style Helpers**: The project includes `throw_if()` and `throw_unless()` helpers from Laravel for cleaner conditional error handling
- **Location**: `src/helpers/functions.php` (autoloaded via composer.json)
- **Usage Patterns**:
  ```php
  // PREFERRED: Simple error message (helpers auto-instantiate RuntimeException)
  throw_unless($entry, "Entry with ID {$entryId} not found");
  throw_if($sectionId === null, 'sectionId is required for new entries');

  // ALTERNATIVE: Explicit exception class for non-RuntimeException cases
  throw_unless($user, \InvalidArgumentException::class, 'User cannot be null');

  // ANTI-PATTERN: Verbose if/throw patterns (avoid these)
  if (!$entry) {
      throw new \RuntimeException("Entry with ID {$entryId} not found");
  }
  ```
- **Best Practices**:
  - Use `throw_unless($value, 'message')` instead of `throw_if($value === null, 'message')`
  - Omit exception class for RuntimeException (default behavior)
  - Only specify exception class when throwing non-RuntimeException types
- **Benefits**: More expressive code, reduced boilerplate, better readability
- **Type Safety**: Includes full PHPStan template annotations for proper static analysis

### PHP Operator Preferences
- **Null Coalescing Assignment (`??=`)**: Prefer the concise null coalescing assignment operator for setting default values:
  ```php
  // PREFERRED: Concise null coalescing assignment
  $siteId ??= Craft::$app->getSites()->getPrimarySite()->id;

  // ANTI-PATTERN: Verbose null check (avoid these)
  if ($siteId === null) {
      $siteId = Craft::$app->getSites()->getPrimarySite()->id;
  }
  ```
- **Benefits**: More readable code, fewer lines, clearer intent

### Type Safety and PHPStan Patterns
- **PHPStan Level**: Project runs at `level: max` (strictest analysis) with official Craft CMS integration
- **Tool Method Type Documentation**: Tool methods use PHPStan docblock types for precise type definitions:
  ```php
  /**
   * Tool description goes in method docblock.
   *
   * Multi-line descriptions are supported and preferred for clarity.
   *
   * @param 'single'|'channel'|'structure' $type PHPStan union types for enums
   * @param array<int> $arrayParam PHPStan array shape for complex types
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public function toolMethod(
      /** Simple inline description for basic parameters */
      string $name,

      /** Multi-line inline descriptions
       * for complex parameters */
      ?array $data = null,
  ): array
  ```
- **Array Return Types**: All methods returning arrays must have PHPDoc annotations:
  ```php
  /**
   * @return array<string, mixed>
   */
  public function methodName(): array
  ```
- **Craft Element Queries**: Craft's `Entry::find()->one()` returns `array|Entry|null`, always use `instanceof` checks:
  ```php
  // CORRECT: Proper type checking
  $entry = Entry::find()->id($id)->one();
  if (!$entry instanceof Entry) {
      throw new \InvalidArgumentException("Entry not found");
  }

  // INCORRECT: Loose null check
  if (!$entry) {
      throw new \InvalidArgumentException("Entry not found");
  }
  ```
- **Nullable Property Access**: Use null-safe operator for potentially null properties:
  ```php
  'postDate' => $entry->postDate?->format('c'),
  'dateUpdated' => $entry->dateUpdated?->format('c'),
  ```
- **Method Parameters**: Specify array types for complex parameters:
  ```php
  /**
   * @param array<string, mixed> $attributeAndFieldData
   */
  public function methodName(array $attributeAndFieldData): void
  ```
- **PHPDoc Positioning**: Place PHPDoc immediately before method declarations:
  ```php
  /**
   * @return array<string, mixed>
   */
  public function methodName(): array
  ```

### PHPStan Integration Achievements
- **Zero Errors at Max Level**: Project successfully passes PHPStan analysis at `level: max` with official Craft CMS integration
- **Comprehensive Type Safety**: All 55+ original errors resolved through systematic type improvements including:
  - Valinor mapper typing with proper error handling
  - Mixed type access fixes replacing defensive checks with proper type assertions
  - Callable handling via PHPStan ignore comments for reflection-based patterns
  - Helper function typing for Laravel-style `throw_if`/`throw_unless` patterns
  - Array type annotations throughout (`@return array<string, mixed>`)
- **Development Workflow**: Established foundation for ongoing static analysis with composer scripts:
  ```bash
  composer phpstan              # Run analysis
  composer phpstan-baseline     # Generate baseline if needed
  ./vendor/bin/phpstan analyse  # Direct execution
  ```
- **Type Safety Patterns**: Key patterns established include:
  - Craft element type checking: `if (!$entry instanceof Entry)` instead of loose null checks
  - Null coalescing assignment: `$siteId ??= Craft::$app->getSites()->getPrimarySite()->id`
  - Defensive programming with PHPStan ignore for necessary runtime checks
  - Laravel-style helper integration with proper type guards

### Performance Considerations
- Valinor validation is cached in production for performance
- Large content operations should use pagination where appropriate
- Query parameter filtering available on list endpoints

### Security
- Anonymous access enabled for API endpoints (required for external integrations)
- CSRF validation disabled for JSON API compatibility
- Input validation handled automatically by Valinor
- Authentication can be added via Craft's native auth system if needed

### File System Safety
- **CRITICAL**: When working with temporary files or scripts, ONLY create them within the project directory (`/home/ubuntu/sites/craft-mcp/plugins/craft-mcp/`)
- **NEVER** create temporary files in `/tmp/` or other system directories
- If temporary files are needed for development tasks, create them in a `temp/` subdirectory within the project
- Clean up any temporary files created during development tasks before committing

## Architecture Decisions

### Why PHAR Distribution over HTTP API?
- No HTTP server setup or configuration required
- Single executable file - easy to deploy and distribute
- Direct integration with Craft - faster execution, no HTTP overhead
- Simpler security model - no exposed web endpoints
- Works offline - no network dependencies
- Better suited for AI agent automation workflows

### Why CLI over HTTP REST API?
- Simpler invocation for AI agents and automation tools
- No need for BASE_URL configuration or HTTP client setup
- Standard exit codes for error handling
- Works in any environment with PHP (no web server needed)
- Clean separation between tool logic and transport layer

### Why Valinor for Validation?
- Automatic type mapping from request data to method parameters
- Runtime type safety without manual validation code
- Excellent error messages for invalid input
- Permissive mode allows flexible type coercion

### Why Controller + Tool Pattern?
- Clean separation of HTTP concerns from business logic
- Controllers handle request/response, tools handle Craft logic
- Tools are reusable across different endpoints
- Easier to test business logic independently

### Why Dependency Injection in Tools?
- Better testability with mock dependencies
- Type-safe dependency resolution via container
- Cleaner code without manual service location
- Follows SOLID principles and best practices
