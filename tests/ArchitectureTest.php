<?php

/*
|--------------------------------------------------------------------------
| Architecture Tests
|--------------------------------------------------------------------------
|
| This file contains architecture tests that enforce coding standards
| and patterns across the MCP tools directory.
|
*/

describe('Architecture: MCP Tools', function () {
    test('tools should not use manual container access')
        ->expect('happycog\craftmcp\tools')
        ->not->toUse([
            'Craft::$container->get',
            '$container->get',
        ]);

    test('tools should not directly access global state')
        ->expect('happycog\craftmcp\tools')
        ->not->toUse([
            '$_GET',
            '$_POST',
            '$_REQUEST',
            '$_SESSION',
            '$_COOKIE',
        ]);
});

describe('Architecture: General Code Quality', function () {
    test('code should not use debug functions')
        ->expect('happycog\craftmcp')
        ->not->toUse([
            'dd',
            'dump',
            'var_dump',
            'print_r',
            'var_export',
        ]);
});

describe('Architecture: MCP Schema Validation', function () {
    test('Schema attributes with array type must define items key', function () {
        $violations = [];
        $toolsDir = __DIR__ . '/../src/tools';

        if (!is_dir($toolsDir)) {
            expect($violations)->toBeEmpty();
            return;
        }

        $files = glob($toolsDir . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $className = basename($file, '.php');

            // Find all Schema attributes with regex
            preg_match_all(
                '/\#\[Schema\s*\(((?:[^()]*|\([^()]*\))*)\)\]/s',
                $content,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($matches[1] as $match) {
                $attributeContent = $match[0];

                // Check if this Schema has type: 'array'
                if (preg_match("/type:\s*['\"]array['\"]/", $attributeContent)) {
                    // Check if it also has items: key
                    if (!preg_match("/items:\s*[\[\{]/", $attributeContent)) {
                        // Find the line number for better error reporting
                        $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                        $violations[] = "$className.php:$lineNumber - Schema with type: 'array' missing 'items' key";
                    }
                }
            }
        }

        expect($violations)
            ->toBeEmpty("All Schema attributes with type: 'array' must define an 'items' key according to the MCP specification. Violations found:\n" . implode("\n", $violations));
    });
});
