<?php

use craft\models\Section;
use happycog\craftmcp\tools\UpdateSection;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\exceptions\ModelSaveException;
use craft\elements\Entry;

beforeEach(function () {
    // Clean up any existing test sections before each test
    $entriesService = Craft::$app->getEntries();
    $testHandles = [
        'updateTestChannel', 'updateTestSingle', 'updateTestStructure', 'typeChangeTest',
        'siteSettingsTest', 'entryTypeAssocTest', 'validationTest', 'errorHandlingTest',
        'duplicateHandleTest', 'propagationTest', 'structureTest'
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
    $this->createdEntryIds = [];

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

    // Helper to create section for testing
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

    // Helper to create entry for type change testing
    $this->createEntry = function (int $sectionId, int $entryTypeId, string $title = 'Test Entry') {
        $sectionsService = Craft::$app->getEntries();
        $section = $sectionsService->getSectionById($sectionId);
        $entryType = $sectionsService->getEntryTypeById($entryTypeId);

        $entry = new Entry([
            'sectionId' => $sectionId,
            'typeId' => $entryTypeId,
            'title' => $title,
            'slug' => \craft\helpers\StringHelper::toKebabCase($title),
        ]);

        if (Craft::$app->getElements()->saveElement($entry)) {
            $this->createdEntryIds[] = $entry->id;
            return $entry;
        }

        throw new \RuntimeException('Failed to create test entry');
    };
});

afterEach(function () {
    // Clean up created entries first
    foreach ($this->createdEntryIds ?? [] as $entryId) {
        $entry = Entry::find()->id($entryId)->one();
        if ($entry instanceof Entry) {
            Craft::$app->getElements()->deleteElement($entry);
        }
    }

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

test('update section tool schema is valid', function () {
    // For modern tools using PHP8 attributes, we can't test the schema directly
    // Instead, we test that the tool can be instantiated without errors
    $tool = new UpdateSection();
    
    expect($tool)->toBeInstanceOf(UpdateSection::class);
    
    // Verify the tool has the expected method with proper attributes
    $reflection = new \ReflectionClass($tool);
    $method = $reflection->getMethod('update');
    
    expect($method)->toBeInstanceOf(\ReflectionMethod::class);
    
    // Verify the method has the McpTool attribute
    $attributes = $method->getAttributes(\PhpMcp\Server\Attributes\McpTool::class);
    expect($attributes)->toHaveCount(1);
    
    // Verify the McpTool attribute has the correct name
    $mcpToolAttribute = $attributes[0]->newInstance();
    expect($mcpToolAttribute->name)->toBe('update_section');
});

test('updates section name successfully', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Test Content');
    $section = ($this->createSection)('Original Name', 'channel', [$entryType['entryTypeId']], ['handle' => 'updateTestChannel']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        name: 'Updated Section Name'
    );

    expect($result['name'])->toBe('Updated Section Name')
        ->and($result['handle'])->toBe('updateTestChannel') // Handle should remain unchanged
        ->and($result['sectionId'])->toBe($section['sectionId'])
        ->and($result['editUrl'])->toContain('/settings/sections/');
});

test('updates section handle successfully', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Test Content');
    $section = ($this->createSection)('Test Section', 'channel', [$entryType['entryTypeId']], ['handle' => 'updateTestSingle']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        handle: 'newSectionHandle'
    );

    expect($result['handle'])->toBe('newSectionHandle')
        ->and($result['name'])->toBe('Test Section') // Name should remain unchanged
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section type from channel to single', function () {
    // Create a test channel section first
    $entryType = ($this->createEntryType)('Type Change Content');
    $section = ($this->createSection)('Type Change Test', 'channel', [$entryType['entryTypeId']], ['handle' => 'typeChangeTest']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        type: 'single'
    );

    expect($result['type'])->toBe(Section::TYPE_SINGLE)
        ->and($result['name'])->toBe('Type Change Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section type from single to channel', function () {
    // Create a test single section first
    $entryType = ($this->createEntryType)('Single To Channel Content');
    $section = ($this->createSection)('Single Section', 'single', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        type: 'channel'
    );

    expect($result['type'])->toBe(Section::TYPE_CHANNEL)
        ->and($result['name'])->toBe('Single Section')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section propagation method', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Propagation Test Content');
    $section = ($this->createSection)('Propagation Test', 'channel', [$entryType['entryTypeId']], ['handle' => 'propagationTest']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        propagationMethod: 'siteGroup'
    );

    expect($result['propagationMethod'])->toBe('siteGroup')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates structure section max levels', function () {
    // Create a test structure section first
    $entryType = ($this->createEntryType)('Structure Test Content');
    $section = ($this->createSection)('Structure Test', 'structure', [$entryType['entryTypeId']], ['handle' => 'structureTest']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        maxLevels: 5
    );

    expect($result['maxLevels'])->toBe(5)
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE)
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates structure section to unlimited levels', function () {
    // Create a test structure section first
    $entryType = ($this->createEntryType)('Unlimited Structure Content');
    $section = ($this->createSection)('Unlimited Structure', 'structure', [$entryType['entryTypeId']], ['maxLevels' => 3]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        maxLevels: 0 // 0 means unlimited
    );

    expect($result['maxLevels'])->toBeNull() // Should be null for unlimited
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE);
});

test('updates site settings for section', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Site Settings Content');
    $section = ($this->createSection)('Site Settings Test', 'channel', [$entryType['entryTypeId']], ['handle' => 'siteSettingsTest']);

    // Get the primary site
    $primarySite = Craft::$app->getSites()->getPrimarySite();

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        siteSettingsData: [
            [
                'siteId' => $primarySite->id,
                'enabledByDefault' => false,
                'hasUrls' => true,
                'uriFormat' => 'custom/{slug}',
                'template' => 'custom/template'
            ]
        ]
    );

    expect($result['sectionId'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe('Site Settings Test');
});

test('updates entry type associations by adding new types', function () {
    // Create entry types and section
    $entryType1 = ($this->createEntryType)('Original Type');
    $entryType2 = ($this->createEntryType)('Additional Type');
    $section = ($this->createSection)('Entry Type Test', 'channel', [$entryType1['entryTypeId']], ['handle' => 'entryTypeAssocTest']);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        entryTypeIds: [$entryType1['entryTypeId'], $entryType2['entryTypeId']]
    );

    expect($result['sectionId'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe('Entry Type Test');

    // Verify the association was updated
    $sectionsService = Craft::$app->getEntries();
    $updatedSection = $sectionsService->getSectionById($section['sectionId']);
    $associatedTypes = $updatedSection->getEntryTypes();
    $associatedIds = array_map(fn($et) => $et->id, $associatedTypes);

    expect($associatedIds)->toContain($entryType1['entryTypeId'], $entryType2['entryTypeId']);
});

test('updates entry type associations by removing types', function () {
    // Create entry types and section
    $entryType1 = ($this->createEntryType)('Keep Type');
    $entryType2 = ($this->createEntryType)('Remove Type');
    $section = ($this->createSection)('Remove Type Test', 'channel', [$entryType1['entryTypeId'], $entryType2['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        entryTypeIds: [$entryType1['entryTypeId']] // Only keep the first one
    );

    expect($result['sectionId'])->toBe($section['sectionId']);

    // Verify the association was updated
    $sectionsService = Craft::$app->getEntries();
    $updatedSection = $sectionsService->getSectionById($section['sectionId']);
    $associatedTypes = $updatedSection->getEntryTypes();
    $associatedIds = array_map(fn($et) => $et->id, $associatedTypes);

    expect($associatedIds)->toContain($entryType1['entryTypeId'])
        ->and($associatedIds)->not->toContain($entryType2['entryTypeId']);
});

test('prevents type change from structure when entries exist', function () {
    // Create a structure section with an entry
    $entryType = ($this->createEntryType)('Structure Content');
    $section = ($this->createSection)('Structure With Entries', 'structure', [$entryType['entryTypeId']]);

    // Create an entry in the section
    ($this->createEntry)($section['sectionId'], $entryType['entryTypeId'], 'Structure Entry');

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        type: 'channel'
    ))->toThrow(RuntimeException::class, 'Structure sections require manual data migration');
});

test('prevents type change to structure when entries exist', function () {
    // Create a channel section with an entry
    $entryType = ($this->createEntryType)('Channel Content');
    $section = ($this->createSection)('Channel With Entries', 'channel', [$entryType['entryTypeId']]);

    // Create an entry in the section
    ($this->createEntry)($section['sectionId'], $entryType['entryTypeId'], 'Channel Entry');

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        type: 'structure'
    ))->toThrow(RuntimeException::class, 'Structure sections require manual data migration');
});

test('allows type change when no entries exist', function () {
    // Create a section without entries
    $entryType = ($this->createEntryType)('Empty Section Content');
    $section = ($this->createSection)('Empty Section', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        type: 'structure'
    );

    expect($result['type'])->toBe(Section::TYPE_STRUCTURE)
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('fails when section does not exist', function () {
    $tool = new UpdateSection();

    expect(fn() => $tool->update(sectionId: 99999))
        ->toThrow(RuntimeException::class, 'Section with ID 99999 not found');
});

test('fails when invalid section type provided', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Validation Test Content');
    $section = ($this->createSection)('Validation Test', 'channel', [$entryType['entryTypeId']], ['handle' => 'validationTest']);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        type: 'invalid'
    ))->toThrow(InvalidArgumentException::class, 'Invalid section type: invalid');
});

test('fails when invalid propagation method provided', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Propagation Validation Content');
    $section = ($this->createSection)('Propagation Validation', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        propagationMethod: 'invalid'
    ))->toThrow(InvalidArgumentException::class, 'Invalid propagation method: invalid');
});

test('fails when invalid default placement provided', function () {
    // Create a test structure section first
    $entryType = ($this->createEntryType)('Placement Validation Content');
    $section = ($this->createSection)('Placement Validation', 'structure', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        defaultPlacement: 'invalid'
    ))->toThrow(InvalidArgumentException::class, 'Invalid default placement: invalid');
});

test('fails when non-existent entry type ID provided', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Entry Type Validation Content');
    $section = ($this->createSection)('Entry Type Validation', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        entryTypeIds: [99999]
    ))->toThrow(RuntimeException::class, 'Entry type with ID 99999 not found');
});

test('fails when non-existent site ID provided in site settings', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Site Validation Content');
    $section = ($this->createSection)('Site Validation', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section['sectionId'],
        siteSettingsData: [
            [
                'siteId' => 99999,
                'enabledByDefault' => true
            ]
        ]
    ))->toThrow(RuntimeException::class, 'Site with ID 99999 not found');
});

test('fails when duplicate handle provided', function () {
    // Create two test sections
    $entryType1 = ($this->createEntryType)('Duplicate Test 1');
    $entryType2 = ($this->createEntryType)('Duplicate Test 2');

    $section1 = ($this->createSection)('First Section', 'channel', [$entryType1['entryTypeId']], ['handle' => 'duplicateHandleTest']);
    $section2 = ($this->createSection)('Second Section', 'channel', [$entryType2['entryTypeId']]);

    $tool = new UpdateSection();

    expect(fn() => $tool->update(
        sectionId: $section2['sectionId'],
        handle: 'duplicateHandleTest' // Same handle as first section
    ))->toThrow(ModelSaveException::class);
});

test('updates multiple properties at once', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Multi Update Content');
    $section = ($this->createSection)('Multi Update Test', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        name: 'Updated Multi Test',
        handle: 'updatedMultiTest',
        propagationMethod: 'language',
        enableVersioning: false
    );

    expect($result['name'])->toBe('Updated Multi Test')
        ->and($result['handle'])->toBe('updatedMultiTest')
        ->and($result['propagationMethod'])->toBe('language')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('preserves existing properties when not updated', function () {
    // Create a test section with specific settings
    $entryType = ($this->createEntryType)('Preserve Test Content');
    $section = ($this->createSection)('Preserve Test', 'structure', [$entryType['entryTypeId']], [
        'propagationMethod' => Section::PROPAGATION_METHOD_SITE_GROUP,
        'maxLevels' => 4
    ]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        name: 'Updated Preserve Test' // Only update name
    );

    expect($result['name'])->toBe('Updated Preserve Test')
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE) // Should preserve type
        ->and($result['propagationMethod'])->toBe('siteGroup') // Should preserve propagation
        ->and($result['maxLevels'])->toBe(4); // Should preserve max levels
});

test('includes control panel URL in response', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Control Panel Content');
    $section = ($this->createSection)('Control Panel Test', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        name: 'Updated Control Panel Test'
    );

    expect($result['editUrl'])->toContain('/settings/sections/')
        ->and($result['editUrl'])->toContain((string)$result['sectionId']);
});

test('handles error handling test case', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Error Handling Content');
    $section = ($this->createSection)('Error Handling Test', 'channel', [$entryType['entryTypeId']], ['handle' => 'errorHandlingTest']);

    $tool = new UpdateSection();

    // Test with missing sectionId
    expect(fn() => $tool->update(sectionId: 0))
        ->toThrow(RuntimeException::class, 'Section with ID 0 not found');
});

test('updates section maxAuthors setting', function () {
    // Create a test section first
    $entryType = ($this->createEntryType)('Max Authors Content');
    $section = ($this->createSection)('Max Authors Test', 'channel', [$entryType['entryTypeId']]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        maxAuthors: 5
    );

    expect($result['maxAuthors'])->toBe(5)
        ->and($result['name'])->toBe('Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section maxAuthors from existing value', function () {
    // Create a test section with initial maxAuthors
    $entryType = ($this->createEntryType)('Update Max Authors Content');
    $section = ($this->createSection)('Update Max Authors Test', 'channel', [$entryType['entryTypeId']], ['maxAuthors' => 2]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        maxAuthors: 10
    );

    expect($result['maxAuthors'])->toBe(10)
        ->and($result['name'])->toBe('Update Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('preserves maxAuthors when not specified in update', function () {
    // Create a test section with initial maxAuthors
    $entryType = ($this->createEntryType)('Preserve Max Authors Content');
    $section = ($this->createSection)('Preserve Max Authors Test', 'channel', [$entryType['entryTypeId']], ['maxAuthors' => 7]);

    $tool = new UpdateSection();
    $result = $tool->update(
        sectionId: $section['sectionId'],
        name: 'Updated Preserve Max Authors Test' // Only update name
    );

    expect($result['maxAuthors'])->toBe(7) // Should preserve original value
        ->and($result['name'])->toBe('Updated Preserve Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});
