<?php

use craft\models\Section;
use happycog\craftmcp\exceptions\ModelSaveException;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;

beforeEach(function () {
    // Clean up any existing test sections before each test
    $entriesService = Craft::$app->getEntries();
    $testHandles = [
        'testNews', 'home', 'sitePages', 'multiSiteContent', 'customSiteSection',
        'controlPanelTest', 'unlimitedStructure', 'duplicateTest'
    ];

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
    $this->createEntryType = function (string $name, string $handle = null) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->create(
            name: $name,
            handle: $handle
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];

        return $result;
    };

    $this->createSection = function (string $name, string $type, array $entryTypeIds, array $options = []) {
        $createSection = Craft::$container->get(CreateSection::class);

        $result = $createSection->create(
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

test('creates channel section with default settings', function () {
    $entryType = ($this->createEntryType)('News Entry');
    $result = ($this->createSection)('Test News', 'channel', [$entryType['entryTypeId']]);

    expect($result['name'])->toBe('Test News')
        ->and($result['handle'])->toBe('testNews')
        ->and($result['type'])->toBe('channel')
        ->and($result['propagationMethod'])->toBe('all')
        ->and($result['sectionId'])->toBeInt()
        ->and($result['editUrl'])->toContain('/settings/sections/');
});

test('creates single section with custom handle', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Single Page');

    $result = ($this->createSection)('Homepage', 'single', [$entryType['entryTypeId']], [
        'handle' => 'home'
    ]);

    expect($result['name'])->toBe('Homepage')
        ->and($result['handle'])->toBe('home')
        ->and($result['type'])->toBe('single');
});

test('creates structure section with hierarchy settings', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Structure Page');

    $result = ($this->createSection)('Site Pages', 'structure', [$entryType['entryTypeId']], [
        'maxLevels' => 3,
        'defaultPlacement' => 'beginning'
    ]);

    expect($result['name'])->toBe('Site Pages')
        ->and($result['type'])->toBe('structure')
        ->and($result['maxLevels'])->toBe(3);
});

test('creates section with custom propagation method', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Multi Site Content');

    $result = ($this->createSection)('Multi Site Content', 'channel', [$entryType['entryTypeId']], [
        'propagationMethod' => 'siteGroup',
        'enableVersioning' => false
    ]);

    expect($result['name'])->toBe('Multi Site Content')
        ->and($result['propagationMethod'])->toBe('siteGroup');
});

test('creates section with site-specific settings', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Custom Site Content');

    // Get the primary site for testing
    $primarySite = Craft::$app->getSites()->getPrimarySite();

    $result = ($this->createSection)('Custom Site Section', 'channel', [$entryType['entryTypeId']], [
        'siteSettings' => [
            [
                'siteId' => $primarySite->id,
                'enabledByDefault' => true,
                'hasUrls' => true,
                'uriFormat' => 'custom/{slug}',
                'template' => 'custom/_entry'
            ]
        ]
    ]);

    expect($result['name'])->toBe('Custom Site Section')
        ->and($result['type'])->toBe('channel');
});

test('fails when section name is missing', function () {
    $tool = new CreateSection();

    expect(fn() => $tool->create('', 'channel', [1]))
        ->toThrow(ModelSaveException::class);
});

test('fails when section type is invalid', function () {
    $tool = new CreateSection();

    expect(fn() => $tool->create('Test Section', 'invalid', [1]))
        ->toThrow(RuntimeException::class, 'Section type must be single, channel, or structure');
});

test('fails when site id is invalid in site settings', function () {
    // First create an entry type to pass validation
    $entryType = ($this->createEntryType)('Site Validation Test');

    $tool = new CreateSection();

    expect(fn() => $tool->create('Test Section', 'channel', [$entryType['entryTypeId']], siteSettings: [
        [
            'siteId' => 99999, // Non-existent site ID
            'enabledByDefault' => true
        ]
    ]))->toThrow(RuntimeException::class, 'Site with ID 99999 not found');
});

test('includes control panel URL in response', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Control Panel Content');

    $result = ($this->createSection)('Control Panel Test', 'channel', [$entryType['entryTypeId']]);

    expect($result['editUrl'])->toContain('/settings/sections/')
        ->and($result['editUrl'])->toContain((string)$result['sectionId']);
});

test('structure section without max levels shows null', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Unlimited Structure Content');

    $result = ($this->createSection)('Unlimited Structure', 'structure', [$entryType['entryTypeId']]);

    expect($result['maxLevels'])->toBeNull()
        ->and($result['type'])->toBe('structure');
});

test('handles duplicate section handle gracefully', function () {
    // First create entry types
    $entryType1 = ($this->createEntryType)('Duplicate Test Content 1');
    $entryType2 = ($this->createEntryType)('Duplicate Test Content 2');

    // First create a section
    ($this->createSection)('Duplicate Test', 'channel', [$entryType1['entryTypeId']], ['handle' => 'duplicateTest']);

    // Try to create another with the same handle
    $tool = new CreateSection();

    expect(fn() => $tool->create('Another Test', 'channel', [$entryType2['entryTypeId']], handle: 'duplicateTest'))
        ->toThrow(\happycog\craftmcp\exceptions\ModelSaveException::class);
});

test('auto-generates handle from name', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Complex Content');

    $result = ($this->createSection)('Complex Entry Type Name With Characters!@#', 'channel', [$entryType['entryTypeId']]);

    expect($result['handle'])->toBe('complexEntryTypeNameWithCharacters')
        ->and($result['name'])->toBe('Complex Entry Type Name With Characters!@#');
});

test('creates structure section with unlimited levels', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Unlimited Pages Content');

    $result = ($this->createSection)('Unlimited Pages', 'structure', [$entryType['entryTypeId']], [
        'maxLevels' => 0 // 0 means unlimited
    ]);

    expect($result['type'])->toBe('structure')
        ->and($result['maxLevels'])->toBeNull(); // Should be null for unlimited
});

test('creates section with maxAuthors setting', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Multi Author Content');

    $result = ($this->createSection)('Multi Author Section', 'channel', [$entryType['entryTypeId']], [
        'maxAuthors' => 3
    ]);

    expect($result['name'])->toBe('Multi Author Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(3);
});

test('creates section without maxAuthors (uses Craft default)', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Standard Content');

    $result = ($this->createSection)('Standard Section', 'channel', [$entryType['entryTypeId']]);

    expect($result['name'])->toBe('Standard Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(1); // Craft CMS default is 1
});

test('creates section with maxAuthors set to 1', function () {
    // First create an entry type
    $entryType = ($this->createEntryType)('Single Author Content');

    $result = ($this->createSection)('Single Author Section', 'channel', [$entryType['entryTypeId']], [
        'maxAuthors' => 1
    ]);

    expect($result['name'])->toBe('Single Author Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(1);
});
