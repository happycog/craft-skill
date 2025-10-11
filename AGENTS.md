# AGENTS.md - Project Documentation for LLMs

## Project Overview

This is a Craft CMS plugin that implements a Model Context Protocol (MCP) server, providing AI assistants with structured access to Craft CMS content management capabilities. The plugin exposes Craft CMS functionality through MCP tools including content creation, modification, search, and management operations.

## Tech Stack

- **Backend**: PHP 8.1+ with Craft CMS 5.x framework
- **MCP Protocol**: php-mcp/server ^3.2 package for MCP specification implementation
- **Transport Layer**: Custom HTTP transport integrated with Yii2 routing (Craft's underlying framework)
- **Session Management**: Custom session handler using Craft's caching system
- **Testing**: Pest PHP testing framework with craft-pest-core
- **Package Management**:
  - PHP: Composer
- **Build Tool**: None required (server-side PHP plugin)

## Project Structure

```
/
├── src/
│   ├── actions/
│   │   └── UpsertEntry.php          # Entry creation/update action
│   ├── attributes/
│   │   ├── BindToContainer.php      # DI container binding attribute
│   │   ├── Init.php                 # Initialization attribute
│   │   └── RegisterListener.php     # Event listener registration
│   ├── base/
│   │   └── Plugin.php               # Base plugin class with DI
│   ├── controllers/
│   │   ├── SseTransportController.php      # Legacy SSE transport
│   │   └── StreamableTransportController.php # Modern HTTP transport
│   ├── session/
│   │   └── CraftSessionHandler.php  # MCP session management
│   ├── tools/                       # MCP tool implementations
│   │   ├── CreateDraft.php          # Draft creation tool
│   │   ├── CreateEntry.php          # Content creation tool
│   │   ├── DeleteEntry.php          # Content deletion tool
│   │   ├── GetEntry.php             # Content retrieval tool
│   │   ├── GetFields.php            # Field schema tool
│   │   ├── GetSections.php          # Section structure tool
│   │   ├── GetSites.php             # Site information tool
│   │   ├── SearchContent.php        # Content search tool
│   │   ├── UpdateDraft.php          # Draft modification tool
│   │   └── UpdateEntry.php          # Content modification tool
│   ├── transports/
│   │   ├── HttpServerTransport.php          # Legacy HTTP transport
│   │   └── StreamableHttpServerTransport.php # Modern streaming transport
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
- php-mcp/server dependency for MCP protocol implementation
- craft-pest-core for testing framework
- PHPStan for static analysis

### 2. `src/Plugin.php`
- Main plugin entry point with dependency injection setup
- MCP server configuration with tool discovery
- HTTP transport registration and route binding
- Session handler integration

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

### MCP Testing
```bash
# Test with MCP inspector (requires bun)
bunx @modelcontextprotocol/inspector --cli http://craft-mcp.dev.markhuot.com/mcp --transport http --method initialize

# Manual curl testing
curl -X POST http://craft-mcp.dev.markhuot.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test", "version": "1.0.0"}}}'
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

### 1. Creating New MCP Tools
Create tool classes in `src/tools/`. Tools use modern PHP8 attributes for schema definition:

**IMPORTANT**: All tools that create, update, or modify content MUST include an explicit instruction in their description to link the user back to the Craft control panel for review. Follow the CreateEntry pattern:

```
After [action] always link the user back to the entry in the Craft control panel so they can review
the changes in the context of the Craft UI.
```

**PREFERRED: Modern PHP8 Attribute Approach (use this for all new tools):**

```php
// src/tools/ExampleTool.php
namespace happycog\craftmcp\tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class ExampleTool
{
    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'example_tool', // Use snake_case, NO craft_ prefix
        description: <<<'END'
        Example tool description that supports multiple lines.

        After performing action always link the user back to the relevant page in the Craft
        control panel so they can review the changes in the context of the Craft UI.
        END
    )]
    public function performAction(
        #[Schema(type: 'string', description: 'Parameter description')]
        string $parameter,

        #[Schema(type: 'integer', description: 'Optional number parameter')]
        ?int $optionalNumber = null
    ): array {
        // Tool implementation logic

        return [
            'success' => true,
            'result' => 'Tool executed successfully',
            'parameter' => $parameter,
        ];
    }
}
```

**LEGACY: Manual Schema Approach (avoid for new tools):**

```php
// DEPRECATED - Only shown for reference, do not use for new tools
namespace happycog\craftmcp\tools;

use PhpMcp\Schema\Tool;
use PhpMcp\Schema\CallToolRequest;
use PhpMcp\Schema\CallToolResult;

class LegacyExampleTool
{
    public function getSchema(): Tool
    {
        return Tool::make(
            name: 'legacy_example_tool',
            description: 'Example tool description',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'parameter' => ['type' => 'string', 'description' => 'Parameter description']
                ],
                'required' => ['parameter']
            ]
        );
    }

    public function execute(CallToolRequest $request): CallToolResult
    {
        $args = $request->params->arguments;

        // Tool implementation logic

        return CallToolResult::make(
            content: [['type' => 'text', 'text' => 'Tool result']]
        );
    }
}
```

### 2. Adding Controller Actions
Controllers use dependency injection and single-action pattern:

```php
// src/controllers/ExampleController.php
namespace happycog\craftmcp\controllers;

use craft\web\Controller;
use craft\web\Response;

class ExampleController extends Controller
{
    protected array|bool|int $allowAnonymous = true;
    public $enableCsrfValidation = false;

    public function actionIndex(ExampleService $service): Response
    {
        $result = $service->performAction();
        return $this->asJson($result);
    }
}
```

### 3. Session Management
Use CraftSessionHandler for MCP session persistence:

```php
$sessionHandler = $container->get(CraftSessionHandler::class);
$sessionId = $sessionHandler->createSession($clientInfo);
$session = $sessionHandler->getSession($sessionId);
```

### 4. Testing
Use Pest with Craft-specific patterns:

```php
// tests/ExampleTest.php
test('tool executes successfully', function () {
    $tool = new ExampleTool();
    $request = CallToolRequest::make(/* ... */);

    $result = $tool->execute($request);

    expect($result)->toBeInstanceOf(CallToolResult::class);
});

// For HTTP endpoint testing
test('endpoint returns valid response', function () {
    $response = $this->post('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [/* ... */]
    ]);

    $response->assertStatus(200);
    $data = $response->json();
    expect($data['jsonrpc'])->toBe('2.0');
});
```

## Current State

### ✅ Completed Setup
- [x] MCP server integration with php-mcp/server package
- [x] HTTP transport with SSE streaming support
- [x] Session management using Craft's caching system
- [x] Tool auto-discovery and registration
- [x] Complete CRUD operations for Craft entries
- [x] Field and section introspection tools
- [x] Content search capabilities
- [x] Comprehensive test suite with Pest
- [x] Complete draft support with CreateDraft, UpdateDraft, and GetSites tools

### 🔄 Ready for Development
- [ ] Enhanced error handling and validation
- [ ] Performance optimization for large content sets
- [ ] Additional MCP capabilities (resources, prompts)
- [ ] Extended field type support
- [ ] Asset management tools

## Important Notes for Future Agents

### MCP Tool Development Guidelines
- **Tool Naming Convention**: Tool names should use snake_case and NOT include a `craft_` prefix. Examples: `create_entry`, `get_sections`, `update_field` (NOT `craft_create_entry`, `craft_get_sections`, etc.)
- **Control Panel Links**: All tools that create, update, or modify Craft content MUST include explicit instructions in their descriptions to link users back to the control panel for review
- **Pattern**: "After [action] always link the user back to the entry in the Craft control panel so they can review the changes in the context of the Craft UI."
- **Implementation**: Use `ElementHelper::elementEditorUrl($entry)` to generate control panel URLs consistently across all tools
- **Examples**: See CreateEntry.php, UpdateEntry.php, and ApplyDraft.php for proper implementation patterns

### MCP Protocol Implementation
- This plugin implements the Model Context Protocol (MCP) specification
- Uses php-mcp/server package for protocol handling - do NOT reimplement MCP manually
- Server capabilities are configured in Plugin.php with tools=true, resources=false, prompts=false
- Tool discovery happens automatically by scanning src/tools/ directory
- **Modern Tools**: Use PHP8 attributes (`#[McpTool]` and `#[Schema]`) with single public method implementation
- **Legacy Tools**: Some tools still use `getSchema()` and `execute()` methods (should be modernized to attributes)

### Craft 5.x Specific Considerations
- **Draft Properties**: Always use `draftName`, `draftNotes`, `isProvisionalDraft` - these are the correct Craft 5.x property names
- **Deprecated Properties**: Never use `revisionName` or `revisionNotes` - these are from older Craft versions
- **API Discovery**: When working with undocumented Craft features, examine the core Element classes and test property access
- **Control Panel URLs**: Use `Craft::$app->getConfig()->general->cpUrl` for generating edit URLs
- **Element Queries**: Draft elements have special query behaviors - they reference canonical entries via `canonicalId`
- **EntryType Properties**: EntryType objects in Craft 5.x DO NOT have `sectionId`, `dateCreated`, or `dateUpdated` properties
- **EntryType-Section Relationship**: To find which section contains an entry type, iterate through all sections and check their `getEntryTypes()` method
- **Standalone Entry Types**: Entry types can exist independently without being associated with a section (useful for Matrix fields)

### Transport Architecture
- **Primary Transport**: StreamableHttpServerTransport for modern MCP clients
- **Legacy Transport**: HttpServerTransport for older SSE-based clients
- **Routes Available**:
  - `POST /mcp` - JSON-RPC message processing
  - `GET /mcp` - SSE streaming for real-time communication
  - `DELETE /mcp` - Session cleanup
  - `GET /sse` - Legacy SSE endpoint
  - `POST /message` - Legacy message endpoint

### Session Management
- Uses CraftSessionHandler with Craft's cache system for persistence
- Sessions are keyed by unique IDs and contain client info and message queues
- Automatic garbage collection removes stale sessions
- Session integration with MCP server's SessionManager for proper protocol handling

### Craft CMS Integration
- Plugin extends craft\base\Plugin with custom DI container
- Uses #[BindToContainer] and #[RegisterListener] attributes for clean architecture
- Tool implementations work directly with Craft's element system
- Respects Craft's permissions and user context when available

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
- Complete test suite covering all MCP tools and transport functionality
- RefreshesDatabase trait ensures clean test isolation
- Tests cover both unit functionality and HTTP endpoint behavior
- **Note**: Some tests may emit warnings due to Craft/Pest framework incompatibilities - this is expected and does not indicate test failure
- Run tests with `./vendor/bin/pest` - all tests should pass despite any warnings
- **Draft Testing Pattern**: When testing draft operations, focus on return value validation rather than database queries due to transaction rollbacks in test environment
- **Property Access**: Use correct Craft 5.x property names in tests (`draftName`, `draftNotes`, `isProvisionalDraft`)

### Error Handling
- Tools should return proper CallToolResult with error content on failures
- HTTP transport handles JSON-RPC error responses automatically
- Session errors are logged and cleaned up gracefully

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
- **Consistent Error Handling**: All save/delete operations should use this pattern for consistent error messages across the MCP tools
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
- **PHPDoc Positioning**: Place PHPDoc immediately before method declarations (before attributes):
  ```php
  /**
   * @return array<string, mixed>
   */
  #[McpTool(name: 'tool_name')]
  public function methodName(): array
  ```

### PHPStan Integration Achievements
- **Zero Errors at Max Level**: Project successfully passes PHPStan analysis at `level: max` with official Craft CMS integration
- **Comprehensive Type Safety**: All 55+ original errors resolved through systematic type improvements including:
  - Session structure typing with detailed array shape definitions (`array<string, array{id: string, created_at: int, messages: array<...>}>`)
  - Mixed type access fixes replacing defensive checks with proper type assertions
  - Callable handling via PHPStan ignore comments for reflection-based patterns
  - Helper function typing for Laravel-style `throw_if`/`throw_unless` patterns
  - Promise template type resolution for React Promise library integration
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
- Tool discovery is cached in production mode (devMode=false)
- Session cleanup prevents memory leaks from abandoned connections
- Large content operations should use pagination where appropriate

### Security
- Anonymous access enabled for MCP endpoints (required for AI assistant access)
- CSRF validation disabled for JSON-RPC compatibility
- Session-based isolation prevents cross-client data leakage
- Input validation handled at tool level

## Architecture Decisions

### Why php-mcp/server over Manual Implementation?
- Provides complete MCP protocol compliance
- Handles JSON-RPC 2.0 specification automatically
- Built-in session management and message routing
- Extensible architecture for future MCP capabilities

### Why Custom HTTP Transport?
- Integrates with Craft's native Yii2 routing system
- Supports both streaming (SSE) and traditional request/response patterns
- Avoids ReactPHP dependency for better Craft compatibility
- Enables proper session persistence across requests

### Why Tool Auto-Discovery?
- Simplifies adding new tools without manual registration
- Follows convention-over-configuration principle
- Enables hot-reloading in development mode
- Maintains clean separation of concerns

### Why Craft-Specific Session Handler?
- Leverages Craft's robust caching system
- Provides automatic cleanup and garbage collection
- Integrates with Craft's configuration and environment system
- Ensures session data persists across PHP requests

This plugin provides a robust foundation for AI assistants to interact with Craft CMS through the standardized MCP protocol.
