#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PHAR Build Script for Agent Craft CLI
 *
 * This script builds a distributable PHAR archive containing:
 * - The plugin's src/ code
 * - The Valinor dependency
 * - A custom autoloader
 * - The CLI entry point
 *
 * Usage: php bin/build-phar.php [output-path]
 */

// Ensure we're running from the project root
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Output path (default or from argument)
$outputPath = $argv[1] ?? $projectRoot . '/agent-craft.phar';

// Remove existing PHAR if it exists
if (file_exists($outputPath)) {
    unlink($outputPath);
}

echo "Building PHAR: {$outputPath}\n";

// Check that phar.readonly is disabled
if (ini_get('phar.readonly')) {
    echo "Error: phar.readonly is enabled. Run with: php -d phar.readonly=0 bin/build-phar.php\n";
    exit(1);
}

try {
    $phar = new Phar($outputPath);
    $phar->startBuffering();

    // Add the custom autoloader
    $autoloader = <<<'PHP'
<?php
// Custom autoloader for PHAR - only loads bundled code
spl_autoload_register(function ($class) {
    // Handle plugin namespace
    if (str_starts_with($class, 'happycog\\craftmcp\\')) {
        $relativeClass = substr($class, strlen('happycog\\craftmcp\\'));
        $file = 'phar://agent-craft.phar/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Handle Valinor namespace
    if (str_starts_with($class, 'CuyZ\\Valinor\\')) {
        $relativeClass = substr($class, strlen('CuyZ\\Valinor\\'));
        $file = 'phar://agent-craft.phar/vendor/cuyz/valinor/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Load helper functions
require 'phar://agent-craft.phar/src/helpers/functions.php';
PHP;

    $phar->addFromString('autoload.php', $autoloader);
    echo "  Added: autoload.php\n";

    // Add src/ directory
    $srcIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $projectRoot . '/src',
            RecursiveDirectoryIterator::SKIP_DOTS
        )
    );

    $srcCount = 0;
    foreach ($srcIterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = 'src/' . substr($file->getPathname(), strlen($projectRoot . '/src/'));
            $phar->addFile($file->getPathname(), $relativePath);
            $srcCount++;
        }
    }
    echo "  Added: {$srcCount} files from src/\n";

    // Add Valinor dependency
    $valinorPath = $projectRoot . '/vendor/cuyz/valinor/src';
    if (!is_dir($valinorPath)) {
        echo "Error: Valinor not found at {$valinorPath}. Run composer install first.\n";
        exit(1);
    }

    $valinorIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $valinorPath,
            RecursiveDirectoryIterator::SKIP_DOTS
        )
    );

    $valinorCount = 0;
    foreach ($valinorIterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = 'vendor/cuyz/valinor/src/' . substr($file->getPathname(), strlen($valinorPath . '/'));
            $phar->addFile($file->getPathname(), $relativePath);
            $valinorCount++;
        }
    }
    echo "  Added: {$valinorCount} files from vendor/cuyz/valinor/\n";

    // Create the bin entry point (modified version of bin/agent-craft)
    $binContent = file_get_contents($projectRoot . '/bin/agent-craft');
    // Remove the shebang as it will be in the stub
    $binContent = preg_replace('/^#!.*\n/', '', $binContent);
    $phar->addFromString('bin/agent-craft.php', $binContent);
    echo "  Added: bin/agent-craft.php\n";

    // Create the stub
    $stub = <<<'STUB'
#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * agent-craft PHAR Bootstrap
 *
 * This stub loads the bundled code from the PHAR, then executes the main CLI script
 * which will bootstrap Craft from the target installation (--path).
 *
 * Architecture:
 * 1. PHAR contains: plugin's src/ code + Valinor dependency
 * 2. Craft's autoloader (loaded later) provides: Craft CMS and other dependencies
 */

Phar::mapPhar('agent-craft.phar');

// Load custom autoloader from inside the PHAR
// This provides access to:
// - happycog\craftmcp\* classes (plugin code)
// - CuyZ\Valinor\* classes (bundled dependency)
require 'phar://agent-craft.phar/autoload.php';

// Execute the main CLI script
// This script will:
// 1. Parse arguments
// 2. Bootstrap Craft CMS from the target installation
// 3. Route and execute commands
require 'phar://agent-craft.phar/bin/agent-craft.php';

__HALT_COMPILER();
STUB;

    $phar->setStub($stub);

    // Sign with SHA256
    $phar->setSignatureAlgorithm(Phar::SHA256);

    $phar->stopBuffering();

    // Make executable
    chmod($outputPath, 0755);

    $size = round(filesize($outputPath) / 1024);
    echo "\nPHAR built successfully!\n";
    echo "  Output: {$outputPath}\n";
    echo "  Size: {$size} KB\n";
    echo "  Files: " . ($srcCount + $valinorCount + 2) . " total\n";

} catch (Exception $e) {
    echo "Error building PHAR: " . $e->getMessage() . "\n";
    exit(1);
}
