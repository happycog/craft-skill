<?php

use craft\elements\Entry;
use craft\models\Section;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\GetSection;

beforeEach(function () {
    // Clean up any existing test sections before each test
    $entriesService = Craft::$app->getEntries();
    $testHandles = ['testGetSection'];

    foreach ($testHandles as $handle) {
        $section = $entriesService->getSectionByHandle($handle);
        if ($section) {
            $entriesService->deleteSection($section);
        }
    }

    // Track created items for cleanup
    $this->createdSectionIds = [];
    $this->createdEntryTypeIds = [];

    // Helper to create entry type for testing
    $this->createEntryType = function (string $name, ?string $handle = null) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->__invoke(
            name: $name,
            handle: $handle
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];

        return $result;
    };

    $this->createSection = function (string $name, string $type, array $entryTypeIds, array $options = []) {
        $createSection = Craft::$container->get(CreateSection::class);

        $result = $createSection->__invoke(
            name: $name,
            type: $type,
            entryTypeIds: $entryTypeIds,
            handle: $options['handle'] ?? null,
            enableVersioning: $options['enableVersioning'] ?? true,
            propagationMethod: $options['propagationMethod'] ?? Section::PROPAGATION_METHOD_ALL,
            maxLevels: $options['maxLevels'] ?? null,
            defaultPlacement: $options['defaultPlacement'] ?? Section::DEFAULT_PLACEMENT_END,
            maxAuthors: $options['maxAuthors'] ?? null,
            siteSettings: $options['siteSettings'] ?? null
        );

        $this->createdSectionIds[] = $result['sectionId'];

        return $result;
    };
});

afterEach(function () {
    // Clean up created sections
    $entriesService = Craft::$app->getEntries();
    foreach ($this->createdSectionIds as $sectionId) {
        $section = $entriesService->getSectionById($sectionId);
        if ($section) {
            $entriesService->deleteSection($section);
        }
    }

    // Clean up created entry types
    foreach ($this->createdEntryTypeIds ?? [] as $entryTypeId) {
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
});

test('retrieves section with entry types and fields', function () {
    // Create an entry type and section
    $entryType = ($this->createEntryType)('Test Entry Type', 'testEntryType');
    $section = ($this->createSection)('Test Get Section', 'channel', [$entryType['entryTypeId']], [
        'handle' => 'testGetSection'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['id'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe('Test Get Section')
        ->and($result['handle'])->toBe('testGetSection')
        ->and($result['type'])->toBe('channel')
        ->and($result['entryTypes'])->toBeArray()
        ->and($result['entryTypes'])->toHaveCount(1)
        ->and($result['entryTypes'][0]['id'])->toBe($entryType['entryTypeId'])
        ->and($result['entryTypes'][0]['name'])->toBe('Test Entry Type')
        ->and($result['entryTypes'][0]['handle'])->toBe('testEntryType');
});

test('includes fields in entry type data', function () {
    // Create an entry type and section
    $entryType = ($this->createEntryType)('Test Entry Type With Fields', 'testEntryTypeFields');
    $section = ($this->createSection)('Test Section Fields', 'channel', [$entryType['entryTypeId']], [
        'handle' => 'testGetSection'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['entryTypes'][0])->toHaveKey('fields')
        ->and($result['entryTypes'][0]['fields'])->toBeArray();
});

test('includes section metadata', function () {
    // Create an entry type and section
    $entryType = ($this->createEntryType)('Test Entry Type Metadata', 'testEntryTypeMeta');
    $section = ($this->createSection)('Test Section Metadata', 'channel', [$entryType['entryTypeId']], [
        'handle' => 'testGetSection',
        'enableVersioning' => false,
        'propagationMethod' => 'siteGroup'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['enableVersioning'])->toBeFalse()
        ->and($result['propagationMethod'])->toBe('siteGroup')
        ->and($result)->toHaveKey('previewTargets');
});

test('throws exception for non-existent section', function () {
    $tool = Craft::$container->get(GetSection::class);

    expect(fn() => $tool->__invoke(sectionId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Section with ID 99999 not found');
});

test('returns multiple entry types for section', function () {
    // Create multiple entry types
    $entryType1 = ($this->createEntryType)('Entry Type One', 'entryTypeOne');
    $entryType2 = ($this->createEntryType)('Entry Type Two', 'entryTypeTwo');

    // Create section with both entry types
    $section = ($this->createSection)('Multi Entry Type Section', 'channel', [
        $entryType1['entryTypeId'],
        $entryType2['entryTypeId']
    ], [
        'handle' => 'testGetSection'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['entryTypes'])->toHaveCount(2)
        ->and($result['entryTypes'][0]['name'])->toBe('Entry Type One')
        ->and($result['entryTypes'][1]['name'])->toBe('Entry Type Two');
});
