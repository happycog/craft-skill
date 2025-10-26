<?php

use happycog\craftmcp\tools\CreateSite;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\DeleteSite;

beforeEach(function () {
    // Use microtime to ensure unique handles across all tests
    $this->uniqueId = str_replace('.', '', microtime(true));

    // Helper to create a test site
    $this->createTestSite = function (string $name = null, string $baseUrl = null): array {
        if ($name === null) {
            $name = 'Test Site ' . $this->uniqueId . mt_rand(1000, 9999);
        }
        if ($baseUrl === null) {
            $baseUrl = 'https://test-' . $this->uniqueId . '.com';
        }

        $tool = new CreateSite();
        return $tool->create(
            name: $name,
            baseUrl: $baseUrl,
            language: 'en-US',
            enabled: true
        );
    };

    // Helper to create a test entry type
    $this->createTestEntryType = function (?string $name = null): array {
        if ($name === null) {
            $name = 'Test Entry Type ' . $this->uniqueId . mt_rand(1000, 9999);
        }
        $tool = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
        return $tool->create($name);
    };

    // Helper to create a test section
    $this->createTestSection = function (array $entryTypeIds): array {
        $tool = new CreateSection();
        return $tool->create(
            name: 'Test Section ' . $this->uniqueId,
            type: 'channel',
            entryTypeIds: $entryTypeIds
        );
    };

    // Helper to create a test entry in a specific site
    $this->createTestEntry = function (int $sectionId, int $entryTypeId, int $siteId, string $title = 'Test Entry'): array {
        $tool = Craft::$container->get(CreateEntry::class);
        return $tool->create($sectionId, $entryTypeId, $siteId, ['title' => $title]);
    };
});

test('deletes site without content successfully', function () {
    // Create a site without any entries
    $siteName = 'Delete Test Empty ' . $this->uniqueId;
    $site = ($this->createTestSite)($siteName);

    $tool = new DeleteSite();
    $result = $tool->delete($site['siteId']);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($site['siteId'])
        ->and($result['name'])->toBe($siteName)
        ->and($result['handle'])->toBe($site['handle'])
        ->and($result['impact']['hasContent'])->toBeFalse()
        ->and($result['impact']['entryCount'])->toBe(0)
        ->and($result['impact']['draftCount'])->toBe(0)
        ->and($result['impact']['revisionCount'])->toBe(0);
});

test('prevents deletion of site with entries without force', function () {
    // Create a site
    $siteName = 'Delete Test With Entries ' . $this->uniqueId;
    $site = ($this->createTestSite)($siteName);

    // Create entry type and section
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)([$entryType['entryTypeId']]);

    // Create an entry for this site
    $entry = ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Test Entry');

    $tool = new DeleteSite();

    expect(fn() => $tool->delete($site['siteId']))
        ->toThrow(RuntimeException::class, "Site '{$siteName}' contains data and cannot be deleted without force=true");
});

test('deletes site with entries when force is true', function () {
    // Create a site
    $siteName = 'Delete Test Force ' . $this->uniqueId;
    $site = ($this->createTestSite)($siteName);

    // Create entry type and section
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)([$entryType['entryTypeId']]);

    // Create an entry for this site
    $entry = ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Test Entry for Force Delete');

    $tool = new DeleteSite();
    $result = $tool->delete($site['siteId'], true);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($site['siteId'])
        ->and($result['name'])->toBe($siteName)
        ->and($result['impact']['hasContent'])->toBeTrue()
        ->and($result['impact']['entryCount'])->toBeGreaterThan(0);
});

test('provides detailed impact assessment', function () {
    // Create a site
    $siteName = 'Delete Test Impact ' . $this->uniqueId;
    $site = ($this->createTestSite)($siteName);

    // Create entry type and section
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)([$entryType['entryTypeId']]);

    // Add multiple entries
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Entry 1');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Entry 2');

    $tool = new DeleteSite();

    expect(fn() => $tool->delete($site['siteId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $site) {
            try {
                $tool->delete($site['siteId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain('Impact Assessment:')
                    ->and($message)->toContain('Entries:')
                    ->and($message)->toContain('Drafts:')
                    ->and($message)->toContain('Revisions:');
            }
        });
});

test('fails when site does not exist', function () {
    $tool = new DeleteSite();

    expect(fn() => $tool->delete(99999))
        ->toThrow(RuntimeException::class, 'Site with ID 99999 not found');
});

test('prevents deletion of primary site', function () {
    // Get the primary site
    $primarySite = Craft::$app->getSites()->getPrimarySite();

    $tool = new DeleteSite();

    expect(fn() => $tool->delete($primarySite->id))
        ->toThrow(RuntimeException::class, 'Cannot delete the primary site. Set another site as primary first.');
});

test('analyzes impact correctly for empty site', function () {
    $site = ($this->createTestSite)('Empty Impact Test ' . $this->uniqueId);

    $tool = new DeleteSite();
    $result = $tool->delete($site['siteId']);

    expect($result['impact'])->toBeArray()
        ->and($result['impact']['hasContent'])->toBeFalse()
        ->and($result['impact']['entryCount'])->toBe(0)
        ->and($result['impact']['draftCount'])->toBe(0)
        ->and($result['impact']['revisionCount'])->toBe(0);
});

test('force parameter validation', function () {
    $site = ($this->createTestSite)('Force Validation Test ' . $this->uniqueId);

    $tool = new DeleteSite();

    // Test with valid force values
    expect($tool->delete($site['siteId'], false))->toBeArray();

    // Create new site for second test
    $site2 = ($this->createTestSite)('Force Validation Test 2 ' . $this->uniqueId);
    expect($tool->delete($site2['siteId'], true))->toBeArray();
});

test('error message includes site name and details', function () {
    $siteName = 'Error Message Test ' . $this->uniqueId;
    $site = ($this->createTestSite)($siteName);

    // Create entry type and section
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)([$entryType['entryTypeId']]);

    // Create an entry for this site
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Error Test Entry');

    $tool = new DeleteSite();

    expect(fn() => $tool->delete($site['siteId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $site, $siteName) {
            try {
                $tool->delete($site['siteId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain($siteName)
                    ->and($message)->toContain('force=true')
                    ->and($message)->toContain('This action cannot be undone');
            }
        });
});

test('successful deletion includes complete information', function () {
    $site = ($this->createTestSite)('Complete Info Test ' . $this->uniqueId);

    $tool = new DeleteSite();
    $result = $tool->delete($site['siteId']);

    expect($result)->toHaveKeys(['id', 'name', 'handle', 'language', 'baseUrl', 'impact'])
        ->and($result['id'])->toBeInt()
        ->and($result['name'])->toBeString()
        ->and($result['handle'])->toBeString()
        ->and($result['language'])->toBeString()
        ->and($result['baseUrl'])->toBeString()
        ->and($result['impact'])->toBeArray();
});

test('handles sites with different languages', function () {
    // Test with different language codes
    $tool = new CreateSite();
    $germanSite = $tool->create(
        name: 'German Site ' . $this->uniqueId,
        baseUrl: 'https://de-' . $this->uniqueId . '.com',
        language: 'de-DE'
    );

    $deleteJob = new DeleteSite();
    $result = $deleteJob->delete($germanSite['siteId']);

    expect($result['name'])->toContain('German Site')
        ->and($result['language'])->toBe('de-DE');
});

test('impact assessment counts are accurate', function () {
    $site = ($this->createTestSite)('Accurate Count Test ' . $this->uniqueId);

    // Create entry type and section
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)([$entryType['entryTypeId']]);

    // Add known number of entries
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Count Entry 1');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Count Entry 2');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], $site['siteId'], 'Count Entry 3');

    $tool = new DeleteSite();

    expect(fn() => $tool->delete($site['siteId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $site) {
            try {
                $tool->delete($site['siteId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain('Entries: 3');
            }
        });
});

test('handles disabled sites', function () {
    // Create a disabled site
    $tool = new CreateSite();
    $disabledSite = $tool->create(
        name: 'Disabled Site ' . $this->uniqueId,
        baseUrl: 'https://disabled-' . $this->uniqueId . '.com',
        language: 'en-US',
        enabled: false
    );

    $deleteJob = new DeleteSite();
    $result = $deleteJob->delete($disabledSite['siteId']);

    expect($result['name'])->toContain('Disabled Site')
        ->and($result['id'])->toBe($disabledSite['siteId']);
});

test('verifies site is removed from database', function () {
    $site = ($this->createTestSite)('Database Removal Test ' . $this->uniqueId);
    $siteId = $site['siteId'];

    $tool = new DeleteSite();
    $tool->delete($siteId);

    // Verify site no longer exists
    $sitesService = Craft::$app->getSites();
    $deletedSite = $sitesService->getSiteById($siteId);

    expect($deletedSite)->toBeNull();
});

test('handles site with @web alias in baseUrl', function () {
    $tool = new CreateSite();
    $site = $tool->create(
        name: 'Web Alias Site ' . $this->uniqueId,
        baseUrl: '@web/test',
        language: 'en-US'
    );

    $deleteJob = new DeleteSite();
    $result = $deleteJob->delete($site['siteId']);

    expect($result['baseUrl'])->toContain('@web/test');
});
