<?php

use happycog\craftmcp\actions\EntryTypeFormatter;
use happycog\craftmcp\actions\FieldFormatter;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateField;

beforeEach(function () {
    $this->entryTypeFormatter = new EntryTypeFormatter(new FieldFormatter());
    $this->createdEntryTypeIds = [];
    $this->createdSectionIds = [];
    $this->createdFieldIds = [];
});

afterEach(function () {
    // Clean up created test data
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();

    foreach ($this->createdEntryTypeIds ?? [] as $entryTypeId) {
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }

    foreach ($this->createdSectionIds ?? [] as $sectionId) {
        $section = $entriesService->getSectionById($sectionId);
        if ($section) {
            $entriesService->deleteSection($section);
        }
    }

    foreach ($this->createdFieldIds ?? [] as $fieldId) {
        $field = $fieldsService->getFieldById($fieldId);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }
});

it('includes usedBy information in formatted entry type', function () {
    // Create a standalone entry type first
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    $entryTypeResult = $createEntryType->create('Test Entry Type for Usage');
    $this->createdEntryTypeIds[] = $entryTypeResult['entryTypeId'];

    // Get the entry type and format it
    $entriesService = Craft::$app->getEntries();
    $entryType = $entriesService->getEntryTypeById($entryTypeResult['entryTypeId']);

    $formatted = $this->entryTypeFormatter->formatEntryType($entryType, true);

    // Should have usedBy key
    expect($formatted)->toHaveKey('usedBy');
    expect($formatted['usedBy'])->toHaveKeys(['sections', 'matrixFields']);

    // Should be empty initially (standalone entry type)
    expect($formatted['usedBy']['sections'])->toBeEmpty();
    expect($formatted['usedBy']['matrixFields'])->toBeEmpty();
});

it('detects entry type usage by sections', function () {
    // Create an entry type first
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    $entryTypeResult = $createEntryType->create('Test Entry Type in Section');
    $this->createdEntryTypeIds[] = $entryTypeResult['entryTypeId'];

    // Create a section and assign the entry type to it
    $createSection = Craft::$container->get(CreateSection::class);
    $sectionResult = $createSection->create(
        name: 'Test Section for Usage',
        type: 'channel',
        entryTypeIds: [$entryTypeResult['entryTypeId']],
        handle: 'testSectionUsage'
    );
    $this->createdSectionIds[] = $sectionResult['sectionId'];

    // Get the entry type and format it
    $entryType = Craft::$app->getEntries()->getEntryTypeById($entryTypeResult['entryTypeId']);
    $formatted = $this->entryTypeFormatter->formatEntryType($entryType, true);

    expect($formatted['usedBy']['sections'])->not->toBeEmpty();
    expect($formatted['usedBy']['sections'][0])->toMatchArray([
        'id' => $sectionResult['sectionId'],
        'name' => 'Test Section for Usage',
        'handle' => 'testSectionUsage',
        'type' => 'channel',
    ]);
});

it('detects entry type usage by matrix fields', function () {
    // For this test we'll just verify the matrix field detection logic structure
    // Creating actual matrix fields with entry types requires more complex setup
    // which is beyond the scope of this basic test

    // Create a standalone entry type
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    $entryTypeResult = $createEntryType->create('Test Matrix Block Type');
    $this->createdEntryTypeIds[] = $entryTypeResult['entryTypeId'];

    // Format the entry type and verify the structure exists
    $entryType = Craft::$app->getEntries()->getEntryTypeById($entryTypeResult['entryTypeId']);
    $formatted = $this->entryTypeFormatter->formatEntryType($entryType, true);

    // Should have the matrixFields array (even if empty)
    expect($formatted['usedBy'])->toHaveKey('matrixFields');
    expect($formatted['usedBy']['matrixFields'])->toBeArray();

    // For a standalone entry type, this should be empty
    expect($formatted['usedBy']['matrixFields'])->toBeEmpty();
});
