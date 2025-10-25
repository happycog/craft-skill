# AGENTS.md - Project Documentation for LLMs

## Project Overview

This is a Craft CMS plugin that provides a RESTful HTTP API with structured access to Craft CMS content management capabilities. The plugin exposes Craft CMS functionality through HTTP endpoints including content creation, modification, search, and management operations.

## Tech Stack

- **Backend**: PHP 8.1+ with Craft CMS 5.x framework
- **Validation**: CuyZ/Valinor ^2.2 for request parameter validation and mapping
- **Transport Layer**: HTTP endpoints integrated with Yii2 routing (Craft's underlying framework)
- **Testing**: Pest PHP testing framework with craft-pest-core
- **Package Management**:
  - PHP: Composer
- **Build Tool**: None required (server-side PHP plugin)

## Project Structure

```
/
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
```

### API Testing
```bash
# IMPORTANT: Base URL and API Prefix Configuration
# The API requires a base URL from PRIMARY_SITE_URL environment variable.
# Check .env file or ENV for PRIMARY_SITE_URL value.
# If not set, ask the user for the base URL to use.
#
# The API prefix is configurable (defaults to 'api').
# Check config/skills.php for 'apiPrefix' in the PHP array first.
# If not found, try the default '/api'.
# If requests fail, ask the user for the configured prefix.
# Example: PRIMARY_SITE_URL=http://craft-mcp.dev.markhuot.com

# Test with curl (replace {BASE_URL} and {API_PREFIX} with actual values)
# Default API prefix is 'api'
curl -X POST {BASE_URL}/{API_PREFIX}/entries \
  -H "Content-Type: application/json" \
  -d '{"sectionId": 1, "entryTypeId": 1, "attributeAndFieldData": {"title": "Test Entry"}}'

# Search entries
curl -X GET "{BASE_URL}/{API_PREFIX}/entries/search?query=test"

# Get specific entry
curl -X GET {BASE_URL}/{API_PREFIX}/entries/123
```

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

### 1. Creating New HTTP Endpoints
Create controller classes in `src/controllers/` and tool classes in `src/tools/`:

**Controller Pattern with Valinor Validation:**

```php
// src/controllers/ExampleController.php
namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\ExampleTool;
use yii\web\Response;

class ExampleController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(ExampleTool::class);
        return $this->callTool($tool->create(...));
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(ExampleTool::class);
        return $this->callTool($tool->get(...), ['id' => $id], useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(ExampleTool::class);
        return $this->callTool($tool->update(...), ['id' => $id]);
    }
}
```

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

**Route Registration in Plugin.php:**

```php
// src/Plugin.php
#[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
{
    $apiPrefix = $this->getSettings()->apiPrefix ?? 'api';
    
    // Example routes
    $event->rules['POST ' . $apiPrefix . '/examples'] = 'mcp/example/create';
    $event->rules['GET ' . $apiPrefix . '/examples/<id>'] = 'mcp/example/get';
    $event->rules['PUT ' . $apiPrefix . '/examples/<id>'] = 'mcp/example/update';
}
```

### 2. Base Controller Features
Controllers extend the base `Controller` class which provides automatic Valinor validation:

```php
// Base controller method that handles validation automatically
protected function callTool(
    callable $tool,
    array $params = [],
    bool $useQueryParams = false
): Response {
    // Automatically maps request body/query params to tool method parameters
    // Validates types and throws 400 errors for invalid input
    // Returns JSON response with tool result
}
```

### 3. Testing
Use Pest with HTTP endpoint testing patterns:

```php
// tests/ExampleTest.php
test('endpoint creates resource successfully', function () {
    $response = $this->post('/api/examples', [
        'parameter' => 'test value',
        'data' => ['key' => 'value']
    ]);

    $response->assertStatus(200);
    $data = $response->json();
    expect($data['success'])->toBeTrue();
    expect($data['parameter'])->toBe('test value');
});

// For GET requests with query parameters
test('endpoint retrieves resource', function () {
    $response = $this->get('/api/examples/123?filter=active');

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('id');
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
- [x] Comprehensive test suite with Pest

### 🔄 Ready for Development
- [ ] Enhanced error handling and validation
- [ ] Performance optimization for large content sets
- [ ] Extended field type support
- [ ] Asset management endpoints
- [ ] User and permission management endpoints

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
- **PRIMARY_SITE_URL Environment Variable**: The standard Craft CMS way to configure the base URL is via the `PRIMARY_SITE_URL` environment variable
- **Configuration Sources**: Check for `PRIMARY_SITE_URL` in:
  1. System environment variables (ENV)
  2. `.env` file in project root
  3. Craft configuration files
- **If Not Set**: If `PRIMARY_SITE_URL` is not defined, ask the user for the base URL to use for API requests
- **API Prefix Configuration**: The API prefix is configurable in multiple locations (defaults to `api`)
  - **Configuration Sources** (check in this order):
    1. `config/skills.php` - PHP array with `apiPrefix` key (e.g., `return ['apiPrefix' => 'custom-api'];`)
    2. Plugin settings via `$this->getSettings()->apiPrefix` in `src/Plugin.php`
    3. Default value: `api`
  - **Usage**: Try the default `/api` first, but check `config/skills.php` if available
  - **If Requests Fail**: Ask the user for the configured API prefix
- **Route Format**: All API routes follow the pattern: `{PRIMARY_SITE_URL}/{apiPrefix}/{endpoint}`
- **Examples**:
  - Default: `https://craft-site.com/api/sections` - List sections
  - Custom prefix: `https://craft-site.com/custom-api/entries` - Create entry
  - With ID: `https://craft-site.com/api/entries/123` - Get entry by ID

### HTTP API Development Guidelines
- **Endpoint Naming Convention**: Use RESTful conventions with plural nouns (e.g., `/api/entries`, `/api/sections`, `/api/fields`)
- **HTTP Methods**: Use appropriate methods - POST for creation, GET for retrieval, PUT for updates, DELETE for deletion
- **Control Panel Links**: All endpoints that create, update, or modify Craft content should include control panel URLs in responses for user review
- **Pattern**: Include a `url` field in response objects with `ElementHelper::elementEditorUrl($element)` for entries
- **Examples**: See EntriesController.php, SectionsController.php for proper implementation patterns

### HTTP API Architecture
- **Request Validation**: Uses Valinor library for automatic type checking and parameter mapping
- **Controller Pattern**: Controllers extend base Controller class with `callTool()` method for validation
- **Tool Layer**: Business logic separated into tool classes in `src/tools/` directory
- **Dependency Injection**: Tools use constructor injection for clean architecture
- **Routes Available**:
  - `POST /api/entries` - Create entry
  - `GET /api/entries/<id>` - Get entry
  - `PUT /api/entries/<id>` - Update entry
  - `DELETE /api/entries/<id>` - Delete entry
  - `GET /api/entries/search` - Search entries
  - `POST /api/sections` - Create section
  - `GET /api/sections` - List sections
  - `PUT /api/sections/<id>` - Update section
  - `DELETE /api/sections/<id>` - Delete section
  - `POST /api/entry-types` - Create entry type
  - `GET /api/entry-types` - List entry types
  - `PUT /api/entry-types/<id>` - Update entry type
  - `DELETE /api/entry-types/<id>` - Delete entry type
  - `POST /api/fields` - Create field
  - `GET /api/fields` - List fields
  - `GET /api/fields/types` - List field types
  - `PUT /api/fields/<id>` - Update field
  - `DELETE /api/fields/<id>` - Delete field
  - `POST /api/drafts` - Create draft
  - `PUT /api/drafts/<id>` - Update draft
  - `POST /api/drafts/<id>/apply` - Apply draft
  - `POST /api/field-layouts` - Create field layout
  - `GET /api/field-layouts` - Get field layout
  - `PUT /api/field-layouts/<id>` - Update field layout
  - `GET /api/sites` - List sites

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

### Why HTTP REST API over Other Protocols?
- Simple, well-understood RESTful patterns
- Easy integration with any HTTP client
- No protocol-specific dependencies required
- Standard JSON request/response format

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

This plugin provides a robust foundation for external systems to interact with Craft CMS through a clean RESTful HTTP API.
