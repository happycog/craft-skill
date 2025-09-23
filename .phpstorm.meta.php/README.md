# PhpStorm & PHPStan Type Hints

This directory contains type hint files for better IDE support and static analysis.

## Files

- **Container.stub** - PHPStan stub file that provides generic type resolution for container `get()` methods
- **container.meta.php** - PhpStorm metadata file that provides IDE type hints for container methods

## Usage

- **PHPStan**: Automatically uses Container.stub via phpstan.neon configuration
- **PhpStorm**: Automatically detects container.meta.php for improved autocomplete and type checking

Both files ensure that `$container->get(ClassName::class)` returns the proper `ClassName` type instead of `mixed`.