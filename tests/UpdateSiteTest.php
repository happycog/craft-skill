<?php

use happycog\craftmcp\exceptions\ModelSaveException;
use happycog\craftmcp\tools\CreateSite;
use happycog\craftmcp\tools\UpdateSite;

beforeEach(function () {
    // Clean up any existing test sites before each test
    $sitesService = Craft::$app->getSites();
    $testHandles = [
        'updateTest', 'nameTest', 'handleTest', 'languageTest', 'urlTest',
        'primaryTest', 'enabledTest', 'multiUpdate', 'preserveTest',
        'duplicateTest', 'duplicateTest2', 'validationTest'
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

test('updates site name successfully', function () {
    // Create a test site first
    $site = ($this->createSite)('Original Name', 'https://test.com', 'en-US', ['handle' => 'updateTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Site Name'
    );

    expect($result['name'])->toBe('Updated Site Name')
        ->and($result['handle'])->toBe('updateTest') // Handle should remain unchanged
        ->and($result['siteId'])->toBe($site['siteId'])
        ->and($result['editUrl'])->toContain('/settings/sites/');
});

test('updates site handle successfully', function () {
    // Create a test site first
    $site = ($this->createSite)('Test Site', 'https://test.com', 'en-US', ['handle' => 'handleTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        handle: 'newSiteHandle'
    );

    expect($result['handle'])->toBe('newSiteHandle')
        ->and($result['name'])->toBe('Test Site') // Name should remain unchanged
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('updates site language successfully', function () {
    // Create a test site first
    $site = ($this->createSite)('Language Test', 'https://test.com', 'en-US', ['handle' => 'languageTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        language: 'de-DE'
    );

    expect($result['language'])->toBe('de-DE')
        ->and($result['name'])->toBe('Language Test')
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('updates site base URL successfully', function () {
    // Create a test site first
    $site = ($this->createSite)('URL Test', 'https://old.com', 'en-US', ['handle' => 'urlTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        baseUrl: 'https://new.com'
    );

    expect($result['baseUrl'])->toBe('https://new.com')
        ->and($result['name'])->toBe('URL Test')
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('updates site primary status', function () {
    // Create a test site first (not primary)
    $site = ($this->createSite)('Primary Test', 'https://test.com', 'en-US', ['handle' => 'primaryTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        primary: true
    );

    expect($result['primary'])->toBeTrue()
        ->and($result['name'])->toBe('Primary Test')
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('updates site enabled status', function () {
    // Create a test site first (enabled by default)
    $site = ($this->createSite)('Enabled Test', 'https://test.com', 'en-US', ['handle' => 'enabledTest']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        enabled: false
    );

    expect($result['enabled'])->toBeFalse()
        ->and($result['name'])->toBe('Enabled Test')
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('updates multiple properties at once', function () {
    // Create a test site first
    $site = ($this->createSite)('Multi Update Test', 'https://old.com', 'en-US', ['handle' => 'multiUpdate']);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Multi Test',
        handle: 'updatedMultiTest',
        baseUrl: 'https://new.com',
        language: 'de-DE',
        enabled: false
    );

    expect($result['name'])->toBe('Updated Multi Test')
        ->and($result['handle'])->toBe('updatedMultiTest')
        ->and($result['baseUrl'])->toBe('https://new.com')
        ->and($result['language'])->toBe('de-DE')
        ->and($result['enabled'])->toBeFalse()
        ->and($result['siteId'])->toBe($site['siteId']);
});

test('preserves existing properties when not updated', function () {
    // Create a test site with specific settings
    $site = ($this->createSite)('Preserve Test', 'https://preserve.com', 'fr-FR', [
        'handle' => 'preserveTest',
        'enabled' => false
    ]);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Preserve Test' // Only update name
    );

    expect($result['name'])->toBe('Updated Preserve Test')
        ->and($result['handle'])->toBe('preserveTest') // Should preserve handle
        ->and($result['language'])->toBe('fr-FR') // Should preserve language
        ->and($result['baseUrl'])->toBe('https://preserve.com') // Should preserve URL
        ->and($result['enabled'])->toBeFalse(); // Should preserve enabled status
});

test('includes control panel URL in response', function () {
    // Create a test site first
    $site = ($this->createSite)('Control Panel Test', 'https://test.com', 'en-US');

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Control Panel Test'
    );

    expect($result['editUrl'])->toContain('/settings/sites/')
        ->and($result['editUrl'])->toContain((string)$result['siteId']);
});

test('fails when site does not exist', function () {
    $tool = new UpdateSite();

    expect(fn() => $tool->update(siteId: 99999))
        ->toThrow(RuntimeException::class, 'Site with ID 99999 not found');
});

test('fails when duplicate handle provided', function () {
    // Create two test sites
    $site1 = ($this->createSite)('First Site', 'https://first.com', 'en-US', ['handle' => 'duplicateTest']);
    $site2 = ($this->createSite)('Second Site', 'https://second.com', 'en-US', ['handle' => 'duplicateTest2']);

    $tool = new UpdateSite();

    expect(fn() => $tool->update(
        siteId: $site2['siteId'],
        handle: 'duplicateTest' // Same handle as first site
    ))->toThrow(ModelSaveException::class);
});

test('verifies site is updated in database', function () {
    // Create a test site first
    $site = ($this->createSite)('Database Test', 'https://old.com', 'en-US');

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Database Test',
        baseUrl: 'https://new.com'
    );

    // Verify site is updated in the database
    $sitesService = Craft::$app->getSites();
    $updatedSite = $sitesService->getSiteById($result['siteId']);

    expect($updatedSite)->not->toBeNull()
        ->and($updatedSite->name)->toBe('Updated Database Test')
        ->and($updatedSite->getBaseUrl())->toBe('https://new.com');
});

test('handles error when updating with invalid site ID', function () {
    $tool = new UpdateSite();

    // Test with missing siteId
    expect(fn() => $tool->update(siteId: 0))
        ->toThrow(RuntimeException::class, 'Site with ID 0 not found');
});

test('updates site from enabled to disabled', function () {
    // Create a test site (enabled by default)
    $site = ($this->createSite)('Enable/Disable Test', 'https://test.com', 'en-US');

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        enabled: false
    );

    expect($result['enabled'])->toBeFalse();

    // Update back to enabled
    $result2 = $tool->update(
        siteId: $site['siteId'],
        enabled: true
    );

    expect($result2['enabled'])->toBeTrue();
});

test('updates site to use @web alias in base URL', function () {
    // Create a test site first
    $site = ($this->createSite)('Alias Test', 'https://test.com', 'en-US');

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        baseUrl: '@web/de'
    );

    expect($result['baseUrl'])->toContain('@web/de');
});

test('preserves primary status when not specified', function () {
    // Create a primary site
    $site = ($this->createSite)('Primary Preserve', 'https://test.com', 'en-US', [
        'primary' => true
    ]);

    $tool = new UpdateSite();
    $result = $tool->update(
        siteId: $site['siteId'],
        name: 'Updated Primary Preserve'
    );

    expect($result['primary'])->toBeTrue() // Should preserve primary status
        ->and($result['name'])->toBe('Updated Primary Preserve');
});
