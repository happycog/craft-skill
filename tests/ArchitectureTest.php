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
