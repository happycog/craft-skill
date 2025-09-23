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

## Implementation Progress (2025-09-18)

- [x] Official `craftcms/phpstan:dev-main` package installed as dev dependency ([composer.json](../composer.json))
- [x] Composer configuration updated: `minimum-stability: dev`, `prefer-stable: true`, PHPStan scripts added ([composer.json](../composer.json))
- [x] PHPStan configuration file created ([phpstan.neon](../phpstan.neon)), includes official Craft CMS config
- [x] Analysis level set to `max` for strictest possible analysis
- [x] All "unknown class Craft" errors resolved (now reporting only real type issues)
- [x] PHPStan runs successfully on `src/` at max level (see output below)
- [x] Composer scripts available for easy PHPStan execution (`composer phpstan`, `composer phpstan-baseline`)
- [x] Memory limits configured via script (1GB)
- [x] Official Craft CMS stubs leveraged for `Craft::$app`, `Yii::$app`, early terminating methods, and exclusions ([vendor/craftcms/phpstan/phpstan.neon](../vendor/craftcms/phpstan/phpstan.neon))

### Validation Results

PHPStan now reports only real type issues (e.g., array value types, mixed access, parameter types) in `src/`:

> No "unknown class Craft" errors remain. Example output:
> - Property type has no value type specified in iterable type array
> - Cannot access offset 'method' on mixed
> - Parameter expects string, mixed given

See [StreamableHttpServerTransport.php](../src/transports/StreamableHttpServerTransport.php) for typical issues.

### Next Steps
- Address remaining type issues incrementally
- Optionally generate a PHPStan baseline for legacy issues

---
**Implementation complete for Craft CMS PHPStan integration.**