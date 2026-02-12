<?php

use Composer\Semver\Semver;
use craft\models\Section;
use happycog\craftmcp\tools\UpdateSection;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\exceptions\ModelSaveException;
use craft\elements\Entry;
use happycog\craftmcp\interfaces\SectionsServiceInterface;

use function happycog\craftmcp\helpers\service;

beforeEach(function () {
    // Track created items for cleanup
    $this->createdSectionIds = [];
    $this->createdEntryTypeIds = [];
    $this->createdEntryIds = [];

    // Helper to create entry type for Craft 5 (unused in Craft 4)
    $this->createEntryType = function (string $name, ?string $handle = null) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->__invoke(
            name: $name,
            handle: $handle
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];

        return $result;
    };

    // Helper to create section for testing
    $this->createSection = function (string $name, string $type, array $options = []) {
        $createSection = Craft::$container->get(CreateSection::class);

        // In Craft 5, create entry types first; in Craft 4, pass null (auto-creates)
        $entryTypeIds = null;
        if (Semver::satisfies(Craft::$app->getVersion(), '>=5.0.0')) {
            $entryType = ($this->createEntryType)($name, \craft\helpers\StringHelper::toHandle($name));
            $entryTypeIds = [$entryType['entryTypeId']];
        }

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

    // Helper to get entry types from a section (works in both Craft 4 and 5)
    $this->getSectionEntryTypes = function (int $sectionId): array {
        $sectionsService = service(SectionsServiceInterface::class);
        $section = $sectionsService->getSectionById($sectionId);
        $entryTypes = $section->getEntryTypes();
        
        return array_map(fn($et) => [
            'entryTypeId' => $et->id,
            'name' => $et->name,
            'handle' => $et->handle,
        ], $entryTypes);
    };

    // Helper to create entry for type change testing
    $this->createEntry = function (int $sectionId, int $entryTypeId, string $title = 'Test Entry') {
        $sectionsService = service(SectionsServiceInterface::class);
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

test('updates section name successfully', function () {
    // Create a test section first
    $section = ($this->createSection)('Original Name', 'channel', ['handle' => 'updateTestChannel']);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        name: 'Updated Section Name'
    );

    expect($result['name'])->toBe('Updated Section Name')
        ->and($result['handle'])->toBe('updateTestChannel') // Handle should remain unchanged
        ->and($result['sectionId'])->toBe($section['sectionId'])
        ->and($result['editUrl'])->toContain('/settings/sections/');
});

test('updates section handle successfully', function () {
    $section = ($this->createSection)('Test Section', 'channel', ['handle' => 'updateTestSingle']);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        handle: 'newSectionHandle'
    );

    expect($result['handle'])->toBe('newSectionHandle')
        ->and($result['name'])->toBe('Test Section') // Name should remain unchanged
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section type from channel to single', function () {
    // Create a test channel section (entry types created automatically)
    $section = ($this->createSection)('Type Change Test', 'channel', ['handle' => 'typeChangeTest']);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'single'
    );

    expect($result['type'])->toBe(Section::TYPE_SINGLE)
        ->and($result['name'])->toBe('Type Change Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section type from single to channel', function () {
    // Create a test single section (entry types created automatically)
    $section = ($this->createSection)('Single Section', 'single');

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'channel'
    );

    expect($result['type'])->toBe(Section::TYPE_CHANNEL)
        ->and($result['name'])->toBe('Single Section')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates section propagation method', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Propagation Test', 'channel', ['handle' => 'propagationTest']);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        propagationMethod: 'siteGroup'
    );

    expect($result['propagationMethod'])->toBe('siteGroup')
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates structure section max levels', function () {
    // Create a test structure section (entry types created automatically)
    $section = ($this->createSection)('Structure Test', 'structure', ['handle' => 'structureTest']);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        maxLevels: 5
    );

    expect($result['maxLevels'])->toBe(5)
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE)
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('updates structure section to unlimited levels', function () {
    // Create a test structure section (entry types created automatically)
    $section = ($this->createSection)('Unlimited Structure', 'structure', ['maxLevels' => 3]);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        maxLevels: 0 // 0 means unlimited
    );

    expect($result['maxLevels'])->toBeNull() // Should be null for unlimited
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE);
});

test('updates site settings for section', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Site Settings Test', 'channel', ['handle' => 'siteSettingsTest']);

    // Get the primary site
    $primarySite = Craft::$app->getSites()->getPrimarySite();

    $tool = new UpdateSection();
    $result = $tool->__invoke(
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
    // In Craft 5, we can create standalone entry types and add them to sections
    // In Craft 4, entry types are created with sections and cannot be moved
    $entryType1 = ($this->createEntryType)('Original Type');
    $entryType2 = ($this->createEntryType)('Additional Type');
    $section = ($this->createSection)('Entry Type Test', 'channel', ['handle' => 'entryTypeAssocTest']);
    
    // Get the auto-created entry type
    $sectionEntryTypes = ($this->getSectionEntryTypes)($section['sectionId']);
    $originalTypeId = $sectionEntryTypes[0]['entryTypeId'];

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        entryTypeIds: [$entryType1['entryTypeId'], $entryType2['entryTypeId']]
    );

    expect($result['sectionId'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe('Entry Type Test');

    // Verify the association was updated
    $sectionsService = service(SectionsServiceInterface::class);
    $updatedSection = $sectionsService->getSectionById($section['sectionId']);
    $associatedTypes = $updatedSection->getEntryTypes();
    $associatedIds = array_map(fn($et) => $et->id, $associatedTypes);

    expect($associatedIds)->toContain($entryType1['entryTypeId'], $entryType2['entryTypeId']);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'), 'Entry type associations only work in Craft 5');

test('updates entry type associations by removing types', function () {
    // In Craft 5, we can create standalone entry types and add/remove them from sections
    // In Craft 4, entry types are created with sections and cannot be moved
    $entryType1 = ($this->createEntryType)('Keep Type');
    $entryType2 = ($this->createEntryType)('Remove Type');
    $section = ($this->createSection)('Remove Type Test', 'channel');

    $tool = new UpdateSection();
    // First add both types
    $tool->__invoke(
        sectionId: $section['sectionId'],
        entryTypeIds: [$entryType1['entryTypeId'], $entryType2['entryTypeId']]
    );
    
    // Then remove one
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        entryTypeIds: [$entryType1['entryTypeId']] // Only keep the first one
    );

    expect($result['sectionId'])->toBe($section['sectionId']);

    // Verify the association was updated
    $sectionsService = service(SectionsServiceInterface::class);
    $updatedSection = $sectionsService->getSectionById($section['sectionId']);
    $associatedTypes = $updatedSection->getEntryTypes();
    $associatedIds = array_map(fn($et) => $et->id, $associatedTypes);

    expect($associatedIds)->toContain($entryType1['entryTypeId'])
        ->and($associatedIds)->not->toContain($entryType2['entryTypeId']);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'), 'Entry type associations only work in Craft 5');

test('prevents type change from structure when entries exist', function () {
    // Create a structure section (entry types created automatically)
    $section = ($this->createSection)('Structure With Entries', 'structure');
    
    // Get the auto-created entry type
    $sectionEntryTypes = ($this->getSectionEntryTypes)($section['sectionId']);
    $entryTypeId = $sectionEntryTypes[0]['entryTypeId'];

    // Create an entry in the section
    ($this->createEntry)($section['sectionId'], $entryTypeId, 'Structure Entry');

    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'channel'
    ))->toThrow(RuntimeException::class, 'Structure sections require manual data migration');
});

test('prevents type change to structure when entries exist', function () {
    // Create a channel section (entry types created automatically)
    $section = ($this->createSection)('Channel With Entries', 'channel');
    
    // Get the auto-created entry type
    $sectionEntryTypes = ($this->getSectionEntryTypes)($section['sectionId']);
    $entryTypeId = $sectionEntryTypes[0]['entryTypeId'];

    // Create an entry in the section
    ($this->createEntry)($section['sectionId'], $entryTypeId, 'Channel Entry');

    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'structure'
    ))->toThrow(RuntimeException::class, 'Structure sections require manual data migration');
});

test('allows type change when no entries exist', function () {
    // Create a section without entries (entry types created automatically)
    $section = ($this->createSection)('Empty Section', 'channel');

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'structure'
    );

    expect($result['type'])->toBe(Section::TYPE_STRUCTURE)
        ->and($result['sectionId'])->toBe($section['sectionId']);
});

test('fails when section does not exist', function () {
    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(sectionId: 99999))
        ->toThrow(RuntimeException::class, 'Section with ID 99999 not found');
});

test('fails when invalid section type provided', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Validation Test', 'channel', ['handle' => 'validationTest']);

    $tool = Craft::$container->get(UpdateSection::class);

    // Tool expects valid type values, invalid values will cause type errors
    // This test verifies that only valid section types are accepted
    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        type: 'invalid' // @phpstan-ignore-line - intentionally invalid for test
    ))->toThrow(UnhandledMatchError::class);
});

test('fails when invalid propagation method provided', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Propagation Validation', 'channel');

    $tool = Craft::$container->get(UpdateSection::class);

    // Tool expects valid propagation method values, invalid values will cause type errors
    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        propagationMethod: 'invalid' // @phpstan-ignore-line - intentionally invalid for test
    ))->toThrow(UnhandledMatchError::class);
});

test('fails when invalid default placement provided', function () {
    // Create a test structure section (entry types created automatically)
    $section = ($this->createSection)('Placement Validation', 'structure');

    $tool = Craft::$container->get(UpdateSection::class);

    // Tool expects valid default placement values, invalid values will cause type errors
    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        defaultPlacement: 'invalid' // @phpstan-ignore-line - intentionally invalid for test
    ))->toThrow(UnhandledMatchError::class);
});

test('fails when non-existent entry type ID provided', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Entry Type Validation', 'channel');

    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(
        sectionId: $section['sectionId'],
        entryTypeIds: [99999]
    ))->toThrow(RuntimeException::class, 'Entry type with ID 99999 not found');
});

test('fails when non-existent site ID provided in site settings', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Site Validation', 'channel');

    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(
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
    // Create two test sections (entry types created automatically)
    $section1 = ($this->createSection)('First Section', 'channel', ['handle' => 'duplicateHandleTest']);
    $section2 = ($this->createSection)('Second Section', 'channel');

    $tool = new UpdateSection();

    expect(fn() => $tool->__invoke(
        sectionId: $section2['sectionId'],
        handle: 'duplicateHandleTest' // Same handle as first section
    ))->toThrow(ModelSaveException::class);
});

test('updates multiple properties at once', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Multi Update Test', 'channel');

    $tool = new UpdateSection();
    $result = $tool->__invoke(
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
    // Create a test section with specific settings (entry types created automatically)
    $section = ($this->createSection)('Preserve Test', 'structure', [
        'propagationMethod' => Section::PROPAGATION_METHOD_SITE_GROUP,
        'maxLevels' => 4
    ]);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        name: 'Updated Preserve Test' // Only update name
    );

    expect($result['name'])->toBe('Updated Preserve Test')
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE) // Should preserve type
        ->and($result['propagationMethod'])->toBe('siteGroup') // Should preserve propagation
        ->and($result['maxLevels'])->toBe(4); // Should preserve max levels
});

test('includes control panel URL in response', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Control Panel Test', 'channel');

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        name: 'Updated Control Panel Test'
    );

    expect($result['editUrl'])->toContain('/settings/sections/')
        ->and($result['editUrl'])->toContain((string)$result['sectionId']);
});

test('handles error handling test case', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Error Handling Test', 'channel', ['handle' => 'errorHandlingTest']);

    $tool = new UpdateSection();

    // Test with missing sectionId
    expect(fn() => $tool->__invoke(sectionId: 0))
        ->toThrow(RuntimeException::class, 'Section with ID 0 not found');
});

test('updates section maxAuthors setting', function () {
    // Create a test section (entry types created automatically)
    $section = ($this->createSection)('Max Authors Test', 'channel');

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        maxAuthors: 5
    );

    expect($result['maxAuthors'])->toBe(5)
        ->and($result['name'])->toBe('Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'), 'maxAuthors only exists in Craft 5');

test('updates section maxAuthors from existing value', function () {
    // Create a test section with initial maxAuthors (entry types created automatically)
    $section = ($this->createSection)('Update Max Authors Test', 'channel', ['maxAuthors' => 2]);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        maxAuthors: 10
    );

    expect($result['maxAuthors'])->toBe(10)
        ->and($result['name'])->toBe('Update Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'), 'maxAuthors only exists in Craft 5');

test('preserves maxAuthors when not specified in update', function () {
    // Create a test section with initial maxAuthors (entry types created automatically)
    $section = ($this->createSection)('Preserve Max Authors Test', 'channel', ['maxAuthors' => 7]);

    $tool = new UpdateSection();
    $result = $tool->__invoke(
        sectionId: $section['sectionId'],
        name: 'Updated Preserve Max Authors Test' // Only update name
    );

    expect($result['maxAuthors'])->toBe(7) // Should preserve original value
        ->and($result['name'])->toBe('Updated Preserve Max Authors Test')
        ->and($result['sectionId'])->toBe($section['sectionId']);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'), 'maxAuthors only exists in Craft 5');
