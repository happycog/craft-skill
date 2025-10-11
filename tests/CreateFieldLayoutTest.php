<?php

use craft\elements\Entry;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\CreateFieldLayout;
use happycog\craftmcp\tools\UpdateEntryType;
use happycog\craftmcp\tools\UpdateFieldLayout;

beforeEach(function () {
    // Clean up any existing test data
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();

    $testHandles = ['testCreateFieldLayout', 'testFieldLayoutFlow', 'testField1', 'testField2'];

    // Clean up test entry types
    foreach ($testHandles as $handle) {
        $entryType = $entriesService->getEntryTypeByHandle($handle);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }

    // Clean up test fields
    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }

    // Track created resources for cleanup
    $this->createdEntryTypeIds = [];
    $this->createdFieldIds = [];
    $this->createdFieldLayoutIds = [];

    $this->createEntryType = function (string $name, array $options = []) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->create(
            name: $name,
            handle: $options['handle'] ?? null,
            hasTitleField: $options['hasTitleField'] ?? false, // Default to false for these tests
            titleTranslationMethod: $options['titleTranslationMethod'] ?? 'site',
            titleTranslationKeyFormat: $options['titleTranslationKeyFormat'] ?? null,
            icon: $options['icon'] ?? null,
            color: $options['color'] ?? null
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];
        return $result;
    };

    $this->createField = function (string $name, array $options = []) {
        $createField = Craft::$container->get(CreateField::class);

        $result = $createField->create(
            name: $name,
            handle: $options['handle'] ?? null,
            type: $options['type'] ?? 'craft\\fields\\PlainText',
            instructions: $options['instructions'] ?? '',
            searchable: $options['searchable'] ?? true,
            translationMethod: $options['translationMethod'] ?? 'none',
            settings: $options['settings'] ?? []
        );

        $this->createdFieldIds[] = $result['fieldId'];
        return $result;
    };

    $this->createFieldLayout = function (string $type) {
        $createFieldLayout = Craft::$container->get(CreateFieldLayout::class);

        $result = $createFieldLayout->create(type: $type);

        $this->createdFieldLayoutIds[] = $result['fieldLayoutId'];
        return $result;
    };
});

afterEach(function () {
    // Clean up created resources
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();

    foreach ($this->createdEntryTypeIds as $entryTypeId) {
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }

    foreach ($this->createdFieldIds as $fieldId) {
        $field = $fieldsService->getFieldById($fieldId);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }

    // Note: Field layouts are typically cleaned up when their associated entry types are deleted
});

test('create field layout tool creates empty layout', function () {
    $result = ($this->createFieldLayout)(Entry::class);

    expect($result)
        ->toHaveKey('fieldLayoutId')
        ->toHaveKey('type')
        ->toHaveKey('tabs');

    expect($result['type'])->toBe(Entry::class);
    expect($result['tabs'])->toBeArray()->toBeEmpty();
    expect($result['fieldLayoutId'])->toBeInt()->toBeGreaterThan(0);

    // Verify the field layout exists in the database
    $fieldsService = Craft::$app->getFields();
    $fieldLayout = $fieldsService->getLayoutById($result['fieldLayoutId']);
    expect($fieldLayout)->not->toBeNull();
    expect($fieldLayout->type)->toBe(Entry::class);
});

test('complete workflow: create entry type, field layout, fields, assign layout', function () {
    // Step 1: Create an entry type with no title field
    $entryTypeResult = ($this->createEntryType)('Test Field Layout Flow', [
        'handle' => 'testFieldLayoutFlow',
        'hasTitleField' => false,
    ]);

    expect($entryTypeResult)
        ->toHaveKey('entryTypeId')
        ->toHaveKey('fieldLayoutId');

    $entryTypeId = $entryTypeResult['entryTypeId'];
    $originalFieldLayoutId = $entryTypeResult['fieldLayoutId'];

    // Step 2: Create a new field layout
    $fieldLayoutResult = ($this->createFieldLayout)(Entry::class);
    $newFieldLayoutId = $fieldLayoutResult['fieldLayoutId'];

    expect($newFieldLayoutId)->toBeInt()->toBeGreaterThan(0);
    expect($newFieldLayoutId)->not->toBe($originalFieldLayoutId);

    // Step 3: Create some fields to add to the layout
    $field1Result = ($this->createField)('Test Field 1', ['handle' => 'testField1']);
    $field2Result = ($this->createField)('Test Field 2', ['handle' => 'testField2']);

    $field1Id = $field1Result['fieldId'];
    $field2Id = $field2Result['fieldId'];

    // Step 4: Add fields to the field layout
    $updateFieldLayout = Craft::$container->get(UpdateFieldLayout::class);
    $updateLayoutResult = $updateFieldLayout->update(
        fieldLayoutId: $newFieldLayoutId,
        tabs: [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => $field1Id, 'required' => true, 'width' => 100],
                    ['fieldId' => $field2Id, 'required' => false, 'width' => 50],
                ]
            ]
        ]
    );

    expect($updateLayoutResult)
        ->toHaveKey('fieldLayout')
        ->and($updateLayoutResult['fieldLayout'])
        ->toHaveKey('tabs')
        ->and($updateLayoutResult['fieldLayout']['tabs'])
        ->toHaveCount(1);

    // Step 5: Assign the field layout to the entry type
    $updateEntryType = Craft::$container->get(UpdateEntryType::class);
    $updateEntryTypeResult = $updateEntryType->update(
        entryTypeId: $entryTypeId,
        fieldLayoutId: $newFieldLayoutId
    );

    expect($updateEntryTypeResult)
        ->toHaveKey('fieldLayoutId')
        ->and($updateEntryTypeResult['fieldLayoutId'])
        ->toBe($newFieldLayoutId);

    // Verify the complete setup by checking the entry type has the correct field layout
    $entriesService = Craft::$app->getEntries();
    $updatedEntryType = $entriesService->getEntryTypeById($entryTypeId);
    
    expect($updatedEntryType)->not->toBeNull();
    expect($updatedEntryType->fieldLayoutId)->toBe($newFieldLayoutId);

    $fieldLayout = $updatedEntryType->getFieldLayout();
    expect($fieldLayout)->not->toBeNull();
    expect($fieldLayout->id)->toBe($newFieldLayoutId);

    $tabs = $fieldLayout->getTabs();
    expect($tabs)->toHaveCount(1);
    expect($tabs[0]->name)->toBe('Content');

    $elements = $tabs[0]->getElements();
    expect($elements)->toHaveCount(2);
});

test('update entry type with invalid field layout id throws error', function () {
    // Create an entry type first
    $entryTypeResult = ($this->createEntryType)('Test Invalid Layout', [
        'handle' => 'testInvalidLayout',
    ]);

    $entryTypeId = $entryTypeResult['entryTypeId'];
    $invalidFieldLayoutId = 99999;

    $updateEntryType = Craft::$container->get(UpdateEntryType::class);

    expect(fn() => $updateEntryType->update(
        entryTypeId: $entryTypeId,
        fieldLayoutId: $invalidFieldLayoutId
    ))->toThrow(RuntimeException::class);
});

test('create field layout with different element types', function () {
    // Test with different element types
    $elementTypes = [
        Entry::class,
        'craft\\elements\\User',
        'craft\\elements\\Asset',
    ];

    foreach ($elementTypes as $elementType) {
        $result = ($this->createFieldLayout)($elementType);

        expect($result)
            ->toHaveKey('fieldLayoutId')
            ->toHaveKey('type')
            ->and($result['type'])
            ->toBe($elementType)
            ->and($result['fieldLayoutId'])
            ->toBeInt()
            ->toBeGreaterThan(0);
    }
});