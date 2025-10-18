# Valinor Integration for Request Validation

The SectionsController demonstrates the use of [CuyZ/Valinor](https://github.com/CuyZ/Valinor) for validating incoming HTTP request data before passing it to tools. Valinor introspects the tool method signatures directly at runtime, eliminating the need for any type duplication.

A custom base `Controller` class provides the `callTool()` method that encapsulates all validation logic, reducing each controller action to a single line.

## Benefits

1. **Zero Duplication**: Valinor reads types directly from tool method signatures
2. **Automatic Validation**: Type checking, default values, and optionality all inferred
3. **Single Source of Truth**: Tool method signatures are the only place types are defined
4. **Better Error Messages**: Valinor provides detailed validation errors for invalid input
5. **One Line Per Action**: Each controller action is literally one line of code
6. **Reusable Pattern**: Base `Controller` class handles all the boilerplate

## Before (Manual Parameter Extraction)

```php
public function actionCreate(): Response
{
    $createSection = Craft::$container->get(CreateSection::class);
    $request = $this->request;
    $bodyParams = $request->getBodyParams();

    $result = $createSection->create(
        name: $bodyParams['name'] ?? '',
        type: $bodyParams['type'] ?? '',
        entryTypeIds: $bodyParams['entryTypeIds'] ?? [],
        handle: $bodyParams['handle'] ?? null,
        enableVersioning: $bodyParams['enableVersioning'] ?? true,
        propagationMethod: $bodyParams['propagationMethod'] ?? 'all',
        maxLevels: $bodyParams['maxLevels'] ?? null,
        defaultPlacement: $bodyParams['defaultPlacement'] ?? 'end',
        maxAuthors: $bodyParams['maxAuthors'] ?? null,
        siteSettings: $bodyParams['siteSettings'] ?? null
    );

    return $this->asJson($result);
}

public function actionUpdate(): Response
{
    $updateSection = Craft::$container->get(UpdateSection::class);
    $request = $this->request;
    $sectionIdParam = $request->getQueryParam('id');
    throw_unless($sectionIdParam !== null && is_scalar($sectionIdParam), 'Section ID is required');
    $sectionId = (int) $sectionIdParam;
    $bodyParams = $request->getBodyParams();

    $result = $updateSection->update(
        sectionId: $sectionId,
        name: $bodyParams['name'] ?? null,
        handle: $bodyParams['handle'] ?? null,
        type: $bodyParams['type'] ?? null,
        entryTypeIds: $bodyParams['entryTypeIds'] ?? null,
        enableVersioning: $bodyParams['enableVersioning'] ?? null,
        propagationMethod: $bodyParams['propagationMethod'] ?? null,
        maxLevels: $bodyParams['maxLevels'] ?? null,
        defaultPlacement: $bodyParams['defaultPlacement'] ?? null,
        maxAuthors: $bodyParams['maxAuthors'] ?? null,
        siteSettingsData: $bodyParams['siteSettings'] ?? null
    );

    return $this->asJson($result);
}
```

## After (Base Controller with callTool)

### Base Controller (src/controllers/Controller.php)
```php
abstract class Controller extends CraftController
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    protected function callTool(
        callable $tool,
        bool $allowPermissiveTypes = false,
        bool $useQueryParams = false
    ): Response {
        try {
            $mapperBuilder = new MapperBuilder();
            if ($allowPermissiveTypes) {
                $mapperBuilder = $mapperBuilder->allowPermissiveTypes();
            }
            $mapper = $mapperBuilder->mapper();
            $source = $useQueryParams 
                ? Source::array($this->request->getQueryParams())
                : Source::array($this->request->getBodyParams());
            // @phpstan-ignore argument.type
            $result = $mapper->map($tool, $source);
            return $this->asJson($result);
        } catch (MappingError $error) {
            $this->response->setStatusCode(400);
            return $this->asJson(['error' => (string) $error]);
        }
    }
}
```

### SectionsController (src/controllers/SectionsController.php)
```php
class SectionsController extends Controller
{
    public function actionCreate(CreateSection $createSection): Response
    {
        return $this->callTool($createSection->create(...));
    }

    public function actionList(GetSections $getSections): Response
    {
        return $this->callTool($getSections->get(...), useQueryParams: true);
    }

    public function actionUpdate(UpdateSection $updateSection): Response
    {
        return $this->callTool($updateSection->update(...));
    }

    public function actionDelete(DeleteSection $deleteSection): Response
    {
        return $this->callTool($deleteSection->delete(...));
    }
}
```

**Complete controller: 32 lines (including imports and blank lines)**  
**Each action: 1 line of actual code**

## Key Improvements

- **One Line Per Action**: Each controller action is literally `return $this->callTool($tool->method(...))`
- **No Type Repetition**: Valinor introspects the callable method signature at runtime
- **No Try/Catch**: Error handling is centralized in the base `Controller::callTool()` method
- **Optional Named Parameters**: Use `useQueryParams: true` for GET requests or `allowPermissiveTypes: true` if needed
- **Reusable**: Any controller can extend the base `Controller` class and get instant Valinor validation

## How It Works

Valinor uses PHP's reflection to introspect the callable's method signature at runtime:

```php
// Tool method signature
public function create(
    string $name,
    string $type,
    array $entryTypeIds,
    ?string $handle = null,
    bool $enableVersioning = true,
    // ... more parameters
): array

// Valinor automatically:
// 1. Reads all parameter types (string, array, ?string, bool, etc.)
// 2. Detects optional parameters (those with defaults)
// 3. Validates incoming data against these types
// 4. Applies default values for missing optional parameters
// 5. Calls the method with validated arguments
```

No need to repeat this information in the controller!

## Using Permissive Types

When tool methods use `array<string, mixed>` or other permissive types:

```php
$mapper = (new MapperBuilder())
    ->allowPermissiveTypes()
    ->mapper();
```

## Pattern Summary

Create controllers by extending the base `Controller` class and using the `callTool()` method:

```php
class MyController extends Controller
{
    public function actionDoSomething(MyTool $myTool): Response
    {
        return $this->callTool($myTool->doSomething(...));
    }
    
    // For GET requests with query params
    public function actionList(MyTool $myTool): Response
    {
        return $this->callTool($myTool->list(...), useQueryParams: true);
    }
    
    // For tools with mixed types
    public function actionUpdate(MyTool $myTool): Response
    {
        return $this->callTool($myTool->update(...), allowPermissiveTypes: true);
    }
}
```

The base `Controller::callTool()` method:
1. Creates Valinor mapper (with optional settings)
2. Maps request data to tool method signature
3. Validates and executes the tool
4. Returns JSON response
5. Catches validation errors and returns 400

The tool method signature is the **single source of truth** for all validation logic. Change the tool signature, and the controller automatically adapts - no controller changes needed!
