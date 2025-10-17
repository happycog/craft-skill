# Skills Conversion - Proof of Concept

## Background

This project is currently set up as a Craft CMS plugin exposing an MCP (Model Context Protocol) server. While MCP provides a complete, standardized protocol for AI agent integration, it requires significant boilerplate and infrastructure to maintain. Anthropic has released a new feature called "Skills" that provides similar functionality through a simpler approach: Markdown files that describe how to interact with HTTP APIs, combined with instructions for Claude on how to use them.

Skills offer a lighter-weight alternative to MCP by:
- Eliminating protocol overhead (no JSON-RPC spec compliance needed)
- Reducing transport complexity (simple HTTP endpoints instead of bi-directional MCP protocol)
- Simplifying agent integration (Markdown-based skill discovery vs MCP server registration)
- Keeping API surface clean while providing structured interaction patterns

This specification outlines a phased migration strategy starting with a proof-of-concept to validate the Skills approach before converting the entire codebase.

## Goal

Establish that converting MCP tools to HTTP endpoints with Skills documentation is feasible, maintainable, and provides equivalent or superior developer experience compared to the current MCP-based approach. Prove this theory by successfully converting the Section management tools (Create, Read, Update, Delete) to HTTP endpoints and documenting them in Anthropic's Skills format.

## Implementation Requirements

### 1. HTTP Endpoint Architecture
- Create RESTful HTTP endpoints following standard conventions:
  - `POST /{apiPrefix}/sections` - Create a new section
  - `GET /{apiPrefix}/sections` - List all sections (supports existing filtering via query parameters)
  - `GET /{apiPrefix}/sections/{id}` - Retrieve a specific section
  - `PUT /{apiPrefix}/sections/{id}` - Update an existing section
  - `DELETE /{apiPrefix}/sections/{id}` - Delete a section
- Implement endpoints as a single Craft controller that calls the existing tool classes
- Controllers serve as a data validation layer before calling tools (to be implemented in Phase 2)
- Tool classes remain unchanged to preserve existing MCP functionality and tests
- Maintain existing tool tests as-is; they don't need to know about HTTP endpoints

### 2. Skills Documentation
- Create a `SKILLS.md` file at the project root that documents the Craft CMS skill in Anthropic's format
- Frontmatter should include:
  - `name`: "Craft CMS Section Management"
  - `description`: Clear description of what the skill does and when Claude should use it
- Document the four Section endpoints with:
  - HTTP method and path
  - Parameter descriptions and types
  - Response schema and examples
  - Usage instructions for Claude
- SKILLS.md is the source of truth for API documentation; no separate OpenAPI spec needed

### 3. Tool-to-Controller Pattern
- Keep existing tool classes completely unchanged in `src/tools/`
- Create a new API controller (e.g., `SectionsController.php`) that instantiates and calls tools
- Controller receives HTTP request parameters, calls appropriate tool method, and returns the JSON response
- This pattern provides a clean separation: tools handle business logic, controllers handle HTTP concerns
- Controller is the future home for request validation (Phase 2)
- Existing tool tests continue to pass without modification

### 4. Routing Integration
- Add new Section API routes in the Plugin.php route registration without breaking existing MCP routes
- API prefix should be configurable via Craft plugin settings (defaults to `api`)
- Ensure MCP transport routes (`/mcp`, `/sse`, `/message`) continue to work unchanged
- API routes are namespaced under the configurable prefix (e.g., `{apiPrefix}/sections`)

## Technical Implementation Notes

### Craft Routing System
- Use Craft's existing Yii2 routing system (already used for MCP transports)
- Register routes in `Plugin.php` via the `RegisterUrlRulesEvent` listener
- Controllers should extend `craft\web\Controller` with `$allowAnonymous = true` for public access
- Set `$enableCsrfValidation = false` for JSON/API compatibility (matching MCP pattern)

### Plugin Configuration
- Add configurable API prefix setting in `config/craft-mcp.php`
- Default prefix should be `api`
- Pass configuration to route registration so routes use the configured prefix
- Example configuration structure:
  ```php
  return [
      'apiPrefix' => 'api',  // or customize to 'craft-api', 'cms', etc.
  ];
  ```

### Response Format
- HTTP endpoints return JSON responses matching the current tool return structures exactly
- Include the same control panel URLs and metadata currently returned by tools
- No custom error formatting at this stage; let exceptions bubble up to Craft/Yii2 for handling

### Skills Format & Distribution
- Follow Anthropic's Skills specification (reference: SKILL.md must have YAML frontmatter with `name` and `description`)
- SKILLS.md serves as both discovery document and detailed API reference
- Skills metadata should be ~100 tokens or less (concise description for context efficiency)
- API documentation in the skill should be clear enough for Claude to call endpoints correctly
- Include endpoint examples with sample parameters and responses
- For this phase, users manually copy/paste SKILLS.md content into their LLM of choice (Claude.ai, API, etc.)
- Phase 2 will add automatic SKILLS.md discovery and serving capabilities

### Error Handling Strategy
- For this proof-of-concept phase, exceptions bubble up to HTTP layer
- Craft and Yii2 automatically convert exceptions to appropriate HTTP error responses
- No custom error formatting needed at this stage - focus on validating the concept works
- Phase 2 will add comprehensive error handling and validation in the controller layer

### Tool Integration Pattern
- Create controllers that instantiate tools via dependency injection (following project patterns)
- Controllers receive request parameters from HTTP context
- Controller calls appropriate tool method with parameters
- Controller returns tool response as JSON via `$this->asJson()`
- Tools remain completely unchanged - existing MCP functionality and tests unaffected

## Non-Requirements (Future Considerations)

- Converting other tool categories (Entries, Fields, etc.) - Phase 2+
- Comprehensive parameter validation and error handling - Phase 2+ (controller layer to be built out)
- Authentication and authorization - Phase 2+
- API versioning strategy - Phase 2+
- Rate limiting - Phase 2+
- OpenAPI/Swagger documentation - Not needed; SKILLS.md is sufficient
- Performance optimization - Phase 2+
- Deprecating MCP interface - Will be removed after all tools converted and Skills approach proven
- HTTP-specific test suite - Not needed for this phase; existing tool tests are sufficient
- SKILLS.md auto-discovery and serving - Phase 2+ (manual copy/paste for this phase)
- Craft plugin settings UI - Phase 2+ (config file only for this phase)

## Acceptance Criteria

- [x] Configurable API prefix setting added to Craft plugin (defaults to `api`)
- [x] `SectionsController` created that calls existing tool classes via dependency injection
- [x] Four Section HTTP endpoints functional and return responses matching existing tool output:
  - [x] `POST /{apiPrefix}/sections` calls `CreateSection` tool
  - [x] `GET /{apiPrefix}/sections` calls `GetSections` tool (preserves filtering)
  - [x] `PUT /{apiPrefix}/sections/{id}` calls `UpdateSection` tool
  - [x] `DELETE /{apiPrefix}/sections/{id}` calls `DeleteSection` tool
- [x] Routes registered in `Plugin.php` without breaking existing MCP routes
- [x] SKILLS.md file created and follows Anthropic Skills format with YAML frontmatter
- [x] SKILLS.md clearly documents all four endpoints with parameters and response examples
- [x] All existing Section tool tests continue to pass unchanged
- [x] Exceptions bubble up to HTTP layer without custom error formatting
- [x] Code follows existing project patterns (dependency injection, controller structure)
- [x] PHPStan analysis passes at level max (consistent with project standards)
- [x] MCP functionality remains unchanged - MCP `/mcp` routes continue to work

## Maintenance Workflow

When updating tools that have HTTP endpoints documented in SKILLS.md, follow this process to keep documentation synchronized:

### Step 1: Identify Documentation Requirements
When modifying a tool class (e.g., `src/tools/CreateSection.php`), check if:
- The tool has HTTP endpoints documented in `SKILLS.md`
- Parameter types, names, or validation logic have changed
- New parameters have been added or existing ones removed
- Default values have changed
- Return value structure has changed

### Step 2: Compare Tool Validation with Documentation
Read both files side-by-side:
```bash
# Example for Section management
src/tools/CreateSection.php
src/tools/UpdateSection.php
src/tools/DeleteSection.php
SKILLS.md (section for "Create a Section", "Update a Section", etc.)
```

Look for discrepancies between:
- PHPDoc parameter types in tool classes (`@var` annotations)
- Parameter validation logic (e.g., `throw_unless`, `in_array` checks)
- Default values in method signatures
- Documentation in SKILLS.md parameter lists

### Step 3: Update SKILLS.md to Match Tool Implementation
Ensure documentation reflects the authoritative source (tool class) by checking:

**Required vs Optional:**
- Tool has parameter with default value → Documentation marks "optional" with default noted
- Tool has parameter without default → Documentation marks "required"

**Type Definitions:**
- Tool uses enum-style PHPDoc (`@var string<'single'|'channel'|'structure'>`) → Documentation lists valid values
- Tool validates array structure → Documentation describes array shape and required keys

**Validation Rules:**
- Tool throws error for invalid values → Documentation states constraints
- Tool has special handling for null/empty → Documentation explains behavior

**Default Behaviors:**
- Tool auto-generates values → Documentation explains when/how
- Tool has conditional logic based on type/mode → Documentation clarifies type-specific parameters

**Example Corrections Made:**
```markdown
# BEFORE (incorrect)
- `entryTypeIds` (array of integers, required): Entry type IDs to assign

# AFTER (matches CreateSection.php line 35-36 and line 62 validation)
- `entryTypeIds` (array of integers, required): Entry type IDs to assign to this section. Can be an empty array to create a section without entry types (uncommon but possible).
```

```markdown
# BEFORE (ambiguous)
- `maxLevels` (integer, optional): Maximum hierarchy levels for structure sections.

# AFTER (clarifies type-specific usage matching line 47-48 and lines 98-106)
- `maxLevels` (integer, optional): Maximum hierarchy levels for structure sections. `null` or `0` for unlimited. Default: `null` (structure sections only)
```

```markdown
# BEFORE (incomplete)
- `siteSettings` (array of objects, optional): Site-specific settings for multi-site installations.

# AFTER (explains default behavior from lines 109-141)
- `siteSettings` (array of objects, optional): Site-specific settings for multi-site installations. If not provided, section will be enabled for all sites with default settings.
```

### Step 4: Verify Documentation Completeness
After updating SKILLS.md, confirm:
- All required parameters are marked as required
- All optional parameters show their default values
- Type-specific parameters note which types they apply to
- Array/object parameters document their structure
- Special behaviors and edge cases are explained
- Validation constraints are documented

### Example Files for Reference
This maintenance pattern was applied to Section management tools:
- Tool implementations: `src/tools/CreateSection.php`, `UpdateSection.php`, `DeleteSection.php`
- Documentation: `SKILLS.md` lines 54-70 (Create), 146-157 (Update), 187-189 (Delete)
- Validation logic sources: Lines 32-36, 47-51, 56, 62-69, 98-106 in CreateSection.php

### When to Perform Documentation Updates
- **Required:** When modifying tool parameter signatures, validation logic, or default values
- **Required:** When adding/removing HTTP endpoints
- **Required:** When changing response structures
- **Recommended:** As part of PR review checklist for tool modifications
- **Best Practice:** Update documentation in the same commit as tool changes to keep history synchronized
