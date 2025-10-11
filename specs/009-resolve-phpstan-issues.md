# Resolve PHPStan Static Analysis Issues

## Background

The current codebase has 38 PHPStan errors across 19 files, all stemming from the same root issue: PHPStan doesn't recognize the `Craft` class, which is the core facade provided by Craft CMS. This is causing "unknown class" errors for all `Craft::$app`, `Craft::$container`, and `Craft::setAlias()` references throughout the codebase.

These errors prevent proper static analysis and continuous integration setup, making it harder to catch actual type safety issues and maintain code quality. The plugin code is functionally correct (all tests pass), but lacks the PHPStan configuration needed to understand Craft CMS's architecture.

## Goal

Configure PHPStan to run at the maximum strictness level with proper Craft CMS integration, eliminating all current static analysis errors while establishing a foundation for ongoing static analysis in the development workflow.

## Implementation Requirements

### 1. Install Official Craft CMS PHPStan Package
- Add `craftcms/phpstan:dev-main` to require-dev dependencies
- Configure composer to allow dev minimum stability for this package
- Use official Craft CMS PHPStan configuration as base

### 2. PHPStan Configuration File
- Create `phpstan.neon` configuration file in project root
- Include `vendor/craftcms/phpstan/phpstan.neon` as base configuration
- Configure analysis paths to include `src/` directory  
- Set PHPStan level to `max` for strictest analysis
- Configure memory limits for Craft CMS analysis

### 3. Leverage Official Craft CMS Stubs
- Use provided BaseYii.stub for `Yii::$app` and related patterns
- Leverage console/Application.stub and web/Application.stub for app context
- Benefit from official earlyTerminatingMethodCalls configuration
- Use scanFiles configuration for proper Craft.php and Yii.php detection

### 3. Composer Configuration Updates
- Set `minimum-stability: dev` and `prefer-stable: true` to allow dev-main package
- Add PHPStan scripts to `composer.json` for easy execution
- Configure memory limits and timeouts for PHPStan execution with Craft CMS

### 4. Baseline Creation (if needed)
- Generate PHPStan baseline for any remaining acceptable issues
- Focus on eliminating `Craft` class unknown issues first
- Document any remaining baseline items with justification

## Technical Implementation Notes

### PHPStan Configuration Structure
```neon
includes:
    - vendor/craftcms/phpstan/phpstan.neon

parameters:
    level: max
    paths:
        - src
```

### Official Craft CMS Integration
The `craftcms/phpstan` package provides:
- **BaseYii.stub**: Proper typing for `Yii::$app` as `\craft\web\Application|\craft\console\Application`
- **Application stubs**: Typing for console and web application contexts
- **Craft.php scanning**: Automatic recognition of the Craft facade class
- **Early terminating methods**: Configuration for `Craft::dd()` and similar debugging methods
- **Exclusion patterns**: Skips Craft CMS internal test files and auto-generated content

### Composer Configuration Updates
```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require-dev": {
        "craftcms/phpstan": "dev-main"
    },
    "scripts": {
        "phpstan": "phpstan --memory-limit=1G",
        "phpstan-baseline": "phpstan --generate-baseline --memory-limit=1G"
    }
}
```

### Memory and Performance Considerations
- Craft CMS analysis may require increased memory limits
- Configure `memory_limit` in PHPStan configuration
- Consider excluding certain Craft core files from analysis
- Cache configuration for faster subsequent runs

## Non-Requirements (Future Considerations)

- Integration with IDE-specific PHPStan plugins
- Advanced type inference for Craft's dynamic field system
- Custom PHPStan rules specific to Craft CMS patterns
- Integration with GitHub Actions or other CI systems
- Analysis of test files (focus on src/ for now)
- Strict analysis of Craft CMS core files vs. plugin code only

## Acceptance Criteria

- [x] **All PHPStan errors resolved** - Project now passes PHPStan analysis at max level with zero errors
- [x] **Type safety improvements implemented** - Fixed mixed type access, callable handling, and defensive programming patterns
- [x] **PHPStan integration complete** - Official Craft CMS PHPStan package provides proper type recognition
- [x] **Tests continue to pass** - All functionality preserved through type safety improvements
- [x] **Documentation updated** - AGENTS.md includes PHPStan integration patterns and best practices

## Implementation Progress (2025-10-10)

### Recent Code Changes Addressed
- [x] **Re-introduced errors resolved** - Recent code changes had introduced 45 new PHPStan errors across tool files
- [x] **CreateEntryType.php fixes** - Fixed titleTranslationMethod type casting, null checks, and property access issues
- [x] **GetEntryType.php fixes** - Fixed MCP schema issues, binary operations, and CP URL property access
- [x] **GetEntryTypes.php fixes** - Fixed MCP schema parameters and type casting issues
- [x] **UpdateEntryType.php fixes** - Fixed identical issues to CreateEntryType.php (18 errors resolved)

### Type Safety Restoration (2025-10-10)
- [x] **Entry Type Tools** - All entry type management tools now pass PHPStan analysis at max level
- [x] **Property Access Safety** - Added proper null checks for EntryType object property access
- [x] **Type Annotation Accuracy** - Fixed return type annotations to match actual return values
- [x] **MCP Schema Compatibility** - Resolved PHPStan conflicts with MCP library type definitions

### Final Validation Results (Updated 2025-10-10)

✅ **PHPStan Analysis: 0 errors**
```bash
./vendor/bin/phpstan analyse
[OK] No errors
```

**Latest Achievement Summary:**
- Successfully addressed **45 re-introduced errors** from recent code changes
- Restored zero-error status at PHPStan max level
- Maintained all functionality while improving type safety
- Re-established foundation for ongoing static analysis

---
**✅ IMPLEMENTATION COMPLETE** - PHPStan integration successfully maintained with zero errors at maximum strictness level.

## Type Safety Improvements (2025-09-18)

- [x] **Session array structure typing** - Added detailed PHPDoc annotations for session data in StreamableHttpServerTransport
- [x] **Mixed type access fixes** - Replaced defensive mixed-type checks with proper type assertions 
- [x] **Callable handling** - Fixed Plugin reflection method callable invocation with PHPStan ignore
- [x] **Helper function typing** - Added proper type guards for throw_if/throw_unless exception handling
- [x] **Promise template types** - Resolved React Promise library template type mismatches
- [x] **Array type annotations** - Added proper `@return array<string, mixed>` documentation throughout
- [x] **Null coalescing patterns** - Utilized PHP 7.4+ `??=` operator for cleaner default value assignment

### Final Validation Results

✅ **PHPStan Analysis: 0 errors**
```bash
./vendor/bin/phpstan analyse
[OK] No errors
```

✅ **Test Suite: All Passing**
```bash
./vendor/bin/pest  
Tests: 2 deprecated, 5 warnings, 118 passed (682 assertions)
```

**Achievement Summary:**
- Reduced from **55+ initial errors** to **0 errors** (100% error elimination)
- Implemented comprehensive type safety improvements across all components
- Maintained full backward compatibility and test coverage
- Established foundation for ongoing static analysis in development workflow

### Key Technical Patterns Established

1. **Session Structure Typing**: Detailed array shape definitions for complex session data
2. **Defensive Programming**: PHPStan ignore comments for necessary runtime checks
3. **Helper Function Integration**: Laravel-style helper functions with proper type safety
4. **Craft CMS Patterns**: Proper integration with Craft's type system and API patterns

---
**✅ IMPLEMENTATION COMPLETE** - PHPStan integration successfully achieved with zero errors at maximum strictness level.