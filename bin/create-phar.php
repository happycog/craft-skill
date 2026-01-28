#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PHAR Build Script for agent-craft CLI Tool
 *
 * Creates a self-contained PHAR executable that bundles:
 * - bin/agent-craft entrypoint
 * - src/ directory with all plugin code
 * - vendor/cuyz/valinor (required dependency)
 * - Composer autoloader for the bundled code
 *
 * The PHAR does NOT include Craft CMS itself.
 * It bootstraps Craft from the target installation (--path).
 *
 * Requirements:
 * - php.ini must have phar.readonly = 0 (or Off)
 * - Run from project root: php bin/create-phar.php
 *
 * Output:
 *  - agent-craft.phar in project root (~2.5 MB)
 */

// Ensure we're running from the project root
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

echo "\n🔨 Building agent-craft PHAR\n";
echo str_repeat('=', 50) . "\n\n";

// Step 1: Check prerequisites
echo "1️⃣  Checking prerequisites...\n";

// Check if Phar class is available
if (!class_exists('Phar')) {
    echo "❌ ERROR: Phar extension is not available in your PHP installation.\n";
    echo "   Please enable the phar extension in your php.ini file.\n";
    exit(1);
}

// Check phar.readonly setting
$pharReadonly = ini_get('phar.readonly');
if ($pharReadonly && $pharReadonly !== 'Off' && $pharReadonly !== '0') {
    echo "❌ ERROR: phar.readonly is enabled in your PHP configuration.\n";
    echo "   To build PHAR files, you need to disable it.\n\n";
    echo "   Options:\n";
    echo "   1. Run with: php -d phar.readonly=0 bin/create-phar.php\n";
    echo "   2. Edit your php.ini file and set: phar.readonly = Off\n\n";
    echo "   Current php.ini: " . php_ini_loaded_file() . "\n";
    exit(1);
}

echo "   ✅ Phar extension available\n";
echo "   ✅ phar.readonly is disabled\n";

// Check required files exist
$requiredFiles = [
    'bin/agent-craft',
    'src/Plugin.php',
    'vendor/autoload.php',
    'composer.json',
];

foreach ($requiredFiles as $file) {
    if (!file_exists($projectRoot . '/' . $file)) {
        echo "❌ ERROR: Required file not found: {$file}\n";
        exit(1);
    }
}

echo "   ✅ All required files present\n\n";

// Step 2: Clean up old PHAR if it exists
echo "2️⃣  Cleaning up old PHAR...\n";

$pharPath = $projectRoot . '/agent-craft.phar';
if (file_exists($pharPath)) {
    if (!unlink($pharPath)) {
        echo "❌ ERROR: Failed to delete existing PHAR: {$pharPath}\n";
        exit(1);
    }
    echo "   ✅ Removed old PHAR\n\n";
} else {
    echo "   ℹ️  No existing PHAR to clean up\n\n";
}

// Step 3: Create new PHAR
echo "3️⃣  Creating PHAR archive...\n";

try {
    $phar = new Phar($pharPath, 0, 'agent-craft.phar');
    
    // Start buffering - builds entire PHAR in memory before writing
    $phar->startBuffering();
    
    echo "   📦 Adding files...\n";
    
    // Add the main entrypoint
    $entrypointContent = file_get_contents($projectRoot . '/bin/agent-craft');
    if ($entrypointContent === false) {
        throw new RuntimeException('Failed to read bin/agent-craft');
    }
    
    // Remove the shebang from the entrypoint since we'll add it to the stub
    $entrypointContent = preg_replace('/^#!\/usr\/bin\/env php\n/', '', $entrypointContent);
    
    $phar->addFromString('bin/agent-craft.php', $entrypointContent);
    echo "      ✓ bin/agent-craft\n";
    
    // Add all files from src/ directory
    echo "      ⏳ Adding src/ directory...\n";
    $srcCount = 0;
    $srcIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot . '/src', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($srcIterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
            $phar->addFile($file->getPathname(), $relativePath);
            $srcCount++;
        }
    }
    echo "      ✓ src/ directory ({$srcCount} files)\n";
    
    // Add Valinor dependency
    echo "      ⏳ Adding vendor/cuyz/valinor...\n";
    $valinorCount = 0;
    $valinorDir = $projectRoot . '/vendor/cuyz/valinor';
    if (!is_dir($valinorDir)) {
        throw new RuntimeException('Valinor not found. Run: composer install');
    }
    
    $valinorIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($valinorDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($valinorIterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
            $phar->addFile($file->getPathname(), $relativePath);
            $valinorCount++;
        }
    }
    echo "      ✓ vendor/cuyz/valinor ({$valinorCount} files)\n";
    
    // Create a custom autoloader for PHAR that only loads what's bundled
    $autoloaderContent = <<<'PHP'
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
    
    $phar->addFromString('autoload.php', $autoloaderContent);
    echo "      ✓ Custom autoloader\n";

    
    // Create the stub - this is the bootstrap code that runs when the PHAR is executed
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
// - Our plugin's src/ code (happycog\craftmcp\)
// - Valinor library (CuyZ\Valinor\)
// - Helper functions
require 'phar://agent-craft.phar/autoload.php';

// Now execute the main CLI logic which will bootstrap Craft from the target installation
// The bin/agent-craft.php script will load Craft's autoloader from the --path location
require 'phar://agent-craft.phar/bin/agent-craft.php';

__HALT_COMPILER();
STUB;
    
    $phar->setStub($stub);
    echo "   ✅ Created PHAR stub\n";
    
    // Stop buffering and write to disk
    $phar->stopBuffering();
    
    // Make the PHAR executable
    chmod($pharPath, 0755);
    
    echo "   ✅ PHAR created successfully\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: Failed to create PHAR\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Verify and report
echo "4️⃣  Verification & Summary\n";

if (!file_exists($pharPath)) {
    echo "❌ ERROR: PHAR file was not created\n";
    exit(1);
}

$pharSize = filesize($pharPath);
$pharSizeMB = number_format($pharSize / 1024 / 1024, 2);

echo "   ✅ PHAR file exists: {$pharPath}\n";
echo "   📊 PHAR size: {$pharSizeMB} MB\n";
echo "   🔒 Permissions: " . substr(sprintf('%o', fileperms($pharPath)), -4) . "\n";

// Test that the PHAR can be loaded
try {
    $testPhar = new Phar($pharPath);
    echo "   ✅ PHAR is valid and can be loaded\n";
} catch (Exception $e) {
    echo "   ❌ WARNING: PHAR validation failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ Build complete!\n\n";
echo "Usage:\n";
echo "  php agent-craft.phar sections/list\n";
echo "  ./agent-craft.phar sections/list\n";
echo "  php agent-craft.phar --path=/path/to/craft entries/create --title=\"Test\"\n\n";
echo "The PHAR contains the plugin code and bootstraps Craft from the target installation.\n";
echo "It can be distributed as a single file and requires PHP 8.1+ and a Craft CMS project.\n\n";
