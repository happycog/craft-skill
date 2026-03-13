<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        return true;
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use markhuot\craftpest\test\RefreshesDatabase;
use markhuot\craftpest\test\TestCase;

pest()->extend(
    TestCase::class,
    RefreshesDatabase::class,
)->in('./');

require_once __DIR__ . '/AddressTestHelpers.php';
require_once __DIR__ . '/UserTestHelpers.php';

beforeEach(function () {
    (function (): void {
        $this->_layouts = null;
    })->call(Craft::$app->getFields());

    (function (): void {
        $this->_sections = null;
        $this->_entryTypes = null;
    })->call(Craft::$app->getEntries());
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
