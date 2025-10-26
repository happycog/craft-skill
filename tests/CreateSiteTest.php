<?php

use happycog\craftmcp\exceptions\ModelSaveException;
use happycog\craftmcp\tools\CreateSite;

beforeEach(function () {
    // Clean up any existing test sites before each test
    $sitesService = Craft::$app->getSites();
    $testHandles = [
        'testSite', 'secondSite', 'germanSite', 'frenchSite', 'customSite',
        'primaryTest', 'disabledTest', 'duplicateTest', 'autoGenHandle'
    ];

    foreach ($testHandles as $handle) {
        $site = $sitesService->getSiteByHandle($handle);
        if ($site) {
            $sitesService->deleteSite($site);
        }
    }

    // Track created items for cleanup
    $this->createdSiteIds = [];

    // Helper to create site for testing
    $this->createSite = function (string $name, string $baseUrl, string $language, array $options = []) {
        $createSite = Craft::$container->get(CreateSite::class);

        $result = $createSite->create(
            name: $name,
            baseUrl: $baseUrl,
            language: $language,
            handle: $options['handle'] ?? null,
            primary: $options['primary'] ?? false,
            enabled: $options['enabled'] ?? true
        );

        $this->createdSiteIds[] = $result['siteId'];

        return $result;
    };
});

afterEach(function () {
    // Clean up created sites
    $sitesService = Craft::$app->getSites();
    foreach ($this->createdSiteIds as $siteId) {
        $site = $sitesService->getSiteById($siteId);
        if ($site) {
            $sitesService->deleteSite($site);
        }
    }
});

test('creates site with default settings', function () {
    $result = ($this->createSite)('Test Site', 'https://test.com', 'en-US');

    expect($result['name'])->toBe('Test Site')
        ->and($result['handle'])->toBe('testSite')
        ->and($result['baseUrl'])->toBe('https://test.com')
        ->and($result['language'])->toBe('en-US')
        ->and($result['primary'])->toBeFalse()
        ->and($result['enabled'])->toBeTrue()
        ->and($result['siteId'])->toBeInt()
        ->and($result['editUrl'])->toContain('/settings/sites/');
});

test('creates site with custom handle', function () {
    $result = ($this->createSite)('Second Site', 'https://second.com', 'en-US', [
        'handle' => 'secondSite'
    ]);

    expect($result['name'])->toBe('Second Site')
        ->and($result['handle'])->toBe('secondSite')
        ->and($result['baseUrl'])->toBe('https://second.com')
        ->and($result['language'])->toBe('en-US');
});

test('creates site with different language', function () {
    $result = ($this->createSite)('German Site', 'https://de.example.com', 'de-DE', [
        'handle' => 'germanSite'
    ]);

    expect($result['name'])->toBe('German Site')
        ->and($result['language'])->toBe('de-DE')
        ->and($result['handle'])->toBe('germanSite');
});

test('creates site with @web base URL', function () {
    $result = ($this->createSite)('Custom Site', '@web/de', 'de-DE', [
        'handle' => 'customSite'
    ]);

    expect($result['name'])->toBe('Custom Site')
        ->and($result['baseUrl'])->toContain('@web/de');
});

test('creates primary site', function () {
    $result = ($this->createSite)('Primary Test', 'https://primary.com', 'en-US', [
        'handle' => 'primaryTest',
        'primary' => true
    ]);

    expect($result['name'])->toBe('Primary Test')
        ->and($result['primary'])->toBeTrue();
});

test('creates disabled site', function () {
    $result = ($this->createSite)('Disabled Test', 'https://disabled.com', 'en-US', [
        'handle' => 'disabledTest',
        'enabled' => false
    ]);

    expect($result['name'])->toBe('Disabled Test')
        ->and($result['enabled'])->toBeFalse();
});

test('auto-generates handle from name', function () {
    $result = ($this->createSite)('Auto Gen Handle!@#', 'https://auto.com', 'en-US');

    expect($result['handle'])->toBe('autoGenHandle')
        ->and($result['name'])->toBe('Auto Gen Handle!@#');
});

test('includes control panel URL in response', function () {
    $result = ($this->createSite)('Control Panel Test', 'https://cp.com', 'en-US');

    expect($result['editUrl'])->toContain('/settings/sites/')
        ->and($result['editUrl'])->toContain((string)$result['siteId']);
});

test('fails when site name is empty', function () {
    $tool = new CreateSite();

    expect(fn() => $tool->create('', 'https://test.com', 'en-US'))
        ->toThrow(\RuntimeException::class, 'Site name cannot be empty');
});

test('fails when base URL is empty', function () {
    $tool = new CreateSite();

    expect(fn() => $tool->create('Test Site', '', 'en-US'))
        ->toThrow(\RuntimeException::class, 'Base URL cannot be empty');
});

test('fails when language is empty', function () {
    $tool = new CreateSite();

    expect(fn() => $tool->create('Test Site', 'https://test.com', ''))
        ->toThrow(\RuntimeException::class, 'Language cannot be empty');
});

test('handles duplicate site handle gracefully', function () {
    // First create a site
    ($this->createSite)('Duplicate Test', 'https://duplicate.com', 'en-US', ['handle' => 'duplicateTest']);

    // Try to create another with the same handle
    $tool = new CreateSite();

    expect(fn() => $tool->create('Another Test', 'https://another.com', 'en-US', handle: 'duplicateTest'))
        ->toThrow(\happycog\craftmcp\exceptions\ModelSaveException::class);
});

test('creates site with French language', function () {
    $result = ($this->createSite)('French Site', 'https://fr.example.com', 'fr-FR', [
        'handle' => 'frenchSite'
    ]);

    expect($result['name'])->toBe('French Site')
        ->and($result['language'])->toBe('fr-FR')
        ->and($result['handle'])->toBe('frenchSite');
});

test('verifies site is saved to database', function () {
    $result = ($this->createSite)('Database Test', 'https://db.test', 'en-US');

    // Verify site exists in the database
    $sitesService = Craft::$app->getSites();
    $site = $sitesService->getSiteById($result['siteId']);

    expect($site)->not->toBeNull()
        ->and($site->name)->toBe('Database Test')
        ->and($site->handle)->toBe('databaseTest')
        ->and($site->language)->toBe('en-US');
});

test('handles special characters in name', function () {
    $result = ($this->createSite)('Spëçiål Chäracters Site', 'https://special.com', 'en-US');

    expect($result['name'])->toBe('Spëçiål Chäracters Site')
        ->and($result['handle'])->toBeString()
        ->and($result['siteId'])->toBeInt();
});
