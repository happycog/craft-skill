# HTTP Endpoints Implementation Summary

## Overview

This document summarizes the implementation of HTTP endpoints for all MCP tools in the Craft CMS plugin. This provides a RESTful API layer on top of the existing MCP protocol tools.

## Architecture

### Controller Pattern

All HTTP endpoints follow a consistent controller pattern:

1. **Base Controller** (`src/controllers/Controller.php`)
   - Extends `craft\web\Controller`
   - Disables CSRF validation for API access
   - Allows anonymous access (configurable)
   - Provides `callTool()` helper method for automatic parameter mapping and validation

2. **Resource Controllers**
   - Each resource group has a dedicated controller
   - Controllers use dependency injection to get tool instances
   - Methods call `callTool()` with the tool method reference and optional parameter overrides

### Parameter Mapping

The `callTool()` method uses **Valinor** for automatic validation and type mapping:

```php
protected function callTool(
    callable $tool,
    array $params = [],
    bool $useQueryParams = false
): Response
```

**Features:**
- **Body Parameters** (POST/PUT): JSON request body mapped to tool parameters
- **Query Parameters** (GET): URL query string mapped to tool parameters
- **Path Parameters**: Route parameters (e.g., `<id>`) merged into parameter mapping
- **Type Safety**: Valinor validates and casts parameters according to tool method signatures
- **Error Handling**: Returns 400 Bad Request with detailed validation errors

### Route Registration

All routes are registered in `Plugin.php` via the `EVENT_REGISTER_SITE_URL_RULES` event:

```php
$apiPrefix = $this->getSettings()->apiPrefix ?? 'api';
$event->rules['POST ' . $apiPrefix . '/sections'] = 'mcp/sections/create';
```

## Implemented Controllers

### 1. SectionsController (`src/controllers/SectionsController.php`)
- `POST /api/sections` → `CreateSection::create()`
- `GET /api/sections` → `GetSections::get()`
- `PUT /api/sections/<id>` → `UpdateSection::update()`
- `DELETE /api/sections/<id>` → `DeleteSection::delete()`

### 2. EntryTypesController (`src/controllers/EntryTypesController.php`)
- `POST /api/entry-types` → `CreateEntryType::create()`
- `GET /api/entry-types` → `GetEntryTypes::getAll()`
- `PUT /api/entry-types/<id>` → `UpdateEntryType::update()`
- `DELETE /api/entry-types/<id>` → `DeleteEntryType::delete()`

### 3. FieldsController (`src/controllers/FieldsController.php`)
- `POST /api/fields` → `CreateField::create()`
- `GET /api/fields` → `GetFields::get()`
- `GET /api/fields/types` → `GetFieldTypes::get()`
- `PUT /api/fields/<id>` → `UpdateField::update()`
- `DELETE /api/fields/<id>` → `DeleteField::delete()`

### 4. EntriesController (`src/controllers/EntriesController.php`)
- `POST /api/entries` → `CreateEntry::create()`
- `GET /api/entries/search` → `SearchContent::search()`
- `GET /api/entries/<id>` → `GetEntry::get()`
- `PUT /api/entries/<id>` → `UpdateEntry::update()`
- `DELETE /api/entries/<id>` → `DeleteEntry::delete()`

### 5. DraftsController (`src/controllers/DraftsController.php`)
- `POST /api/drafts` → `CreateDraft::create()`
- `PUT /api/drafts/<id>` → `UpdateDraft::update()`
- `POST /api/drafts/<id>/apply` → `ApplyDraft::apply()`

### 6. FieldLayoutsController (`src/controllers/FieldLayoutsController.php`)
- `POST /api/field-layouts` → `CreateFieldLayout::create()`
- `GET /api/field-layouts` → `GetFieldLayout::get()`
- `PUT /api/field-layouts/<id>` → `UpdateFieldLayout::update()`

### 7. SitesController (`src/controllers/SitesController.php`)
- `GET /api/sites` → `GetSites::get()`

## Implementation Details

### Controller Example

```php
class SectionsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateSection::class);
        return $this->callTool($tool->create(...));
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateSection::class);
        // Merge path parameter 'id' as 'sectionId'
        return $this->callTool($tool->update(...), ['sectionId' => $id]);
    }

    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetSections::class);
        // Use query parameters for GET requests
        return $this->callTool($tool->get(...), useQueryParams: true);
    }
}
```

### Route Pattern

```php
// POST /api/sections → SectionsController::actionCreate()
$event->rules['POST ' . $apiPrefix . '/sections'] = 'mcp/sections/create';

// GET /api/sections → SectionsController::actionList()
$event->rules['GET ' . $apiPrefix . '/sections'] = 'mcp/sections/list';

// PUT /api/sections/1 → SectionsController::actionUpdate(1)
$event->rules['PUT ' . $apiPrefix . '/sections/<id>'] = 'mcp/sections/update';

// DELETE /api/sections/1 → SectionsController::actionDelete(1)
$event->rules['DELETE ' . $apiPrefix . '/sections/<id>'] = 'mcp/sections/delete';
```

## Testing

All endpoints have been validated through:

1. **PHPStan Analysis** (Level Max)
   - All controllers pass strict type checking
   - No errors in Plugin.php route registration

2. **Existing Test Suite**
   - All tool tests continue to pass
   - Architecture tests validate proper dependency injection patterns

3. **Manual Testing**
   - Endpoints can be tested with curl or HTTP clients
   - Valinor provides automatic validation feedback

## Configuration

### Custom API Prefix

Users can customize the API prefix via plugin settings:

```php
// config/mcp.php
return [
    'apiPrefix' => 'v1/api', // Default: 'api'
];
```

## Benefits

1. **RESTful Access**: Standard HTTP API for all MCP tools
2. **Type Safety**: Automatic parameter validation via Valinor
3. **Consistency**: Uniform pattern across all resource endpoints
4. **Zero Code Duplication**: Controllers delegate directly to existing tool classes
5. **Easy Testing**: Standard HTTP requests via curl or API clients
6. **Extensibility**: Easy to add new endpoints following the established pattern

## Documentation

Three levels of documentation have been created:

1. **[http-api.md](http-api.md)**: Complete API reference with examples
2. **[routes.md](routes.md)**: Quick reference table of all routes
3. **[SKILLS.md](../SKILLS.md)**: User-facing skill documentation

## Future Enhancements

Potential improvements to consider:

1. **Authentication**: Add API key or OAuth support
2. **Rate Limiting**: Implement request throttling
3. **Versioning**: Support multiple API versions
4. **Webhooks**: Event notifications for content changes
5. **Batch Operations**: Multi-resource operations in single requests
6. **GraphQL Alternative**: Complement REST API with GraphQL endpoint
