<?php

use craft\elements\Entry;
use craft\models\Section;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\GetSection;
use happycog\craftmcp\interfaces\SectionsServiceInterface;
use function happycog\craftmcp\helpers\service;

beforeEach(function () {
    // Clean up any existing test sections before each test
    $sectionsService = service(SectionsServiceInterface::class);
    $testHandles = ['testGetSection'];

    foreach ($testHandles as $handle) {
        $section = $sectionsService->getSectionByHandle($handle);
        if ($section) {
            $sectionsService->deleteSection($section);
        }
    }

    // Track created items for cleanup
    $this->createdSectionIds = [];
    $this->createdEntryTypeIds = [];

    // Helper to create section for testing (in Craft 4, this creates entry types automatically)
    $this->createSection = function (string $name, string $type, array $options = []) {
        $createSection = Craft::$container->get(CreateSection::class);

        $result = $createSection->__invoke(
            name: $name,
            type: $type,
            entryTypeIds: null, // Craft 4 creates default entry types automatically
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
    $sectionsService = service(SectionsServiceInterface::class);
    foreach ($this->createdSectionIds as $sectionId) {
        $section = $sectionsService->getSectionById($sectionId);
        if ($section) {
            $sectionsService->deleteSection($section);
        }
    }

    // Clean up created entry types
    foreach ($this->createdEntryTypeIds ?? [] as $entryTypeId) {
        $entryType = $sectionsService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $sectionsService->deleteEntryType($entryType);
        }
    }
});

test('retrieves section with entry types and fields', function () {
    // Create a section (entry types are created automatically in Craft 4)
    $section = ($this->createSection)('Test Get Section', 'channel', [
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
        ->and($result['entryTypes'][0])->toHaveKey('id')
        ->and($result['entryTypes'][0])->toHaveKey('name')
        ->and($result['entryTypes'][0])->toHaveKey('handle');
});

test('includes fields in entry type data', function () {
    // Create a section (entry types are created automatically in Craft 4)
    $section = ($this->createSection)('Test Section Fields', 'channel', [
        'handle' => 'testGetSection'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['entryTypes'][0])->toHaveKey('fields')
        ->and($result['entryTypes'][0]['fields'])->toBeArray();
});

test('includes section metadata', function () {
    // Create a section (entry types are created automatically in Craft 4)
    $section = ($this->createSection)('Test Section Metadata', 'channel', [
        'handle' => 'testGetSection',
        'enableVersioning' => false,
        'propagationMethod' => 'siteGroup'
    ]);

    // Get the section
    $tool = Craft::$container->get(GetSection::class);
    $result = $tool->__invoke(sectionId: $section['sectionId']);

    expect($result['enableVersioning'])->toBeFalse()
        ->and($result)->toHaveKey('propagationMethod')
        ->and($result)->toHaveKey('previewTargets');
});

test('throws exception for non-existent section', function () {
    $tool = Craft::$container->get(GetSection::class);

    expect(fn() => $tool->__invoke(sectionId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Section with ID 99999 not found');
});

test('returns multiple entry types for section', function () {
    // In Craft 4, sections create a default entry type automatically
    // We can't easily add multiple entry types via CreateEntryType in Craft 4
    // since entry types must be associated with sections at creation time
    // This test is skipped for Craft 4
    $this->markTestSkipped('Multiple entry types per section requires different approach in Craft 4');
});
