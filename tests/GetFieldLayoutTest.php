<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\GetFieldLayout;

beforeEach(function () {
    // Clean up any existing test data
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();

    $testHandles = ['testGetFieldLayout', 'testField1', 'testField2'];

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

    $this->createEntryType = function (string $name, array $options = []) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->create(
            name: $name,
            handle: $options['handle'] ?? null,
            hasTitleField: $options['hasTitleField'] ?? true,
            titleTranslationMethod: $options['titleTranslationMethod'] ?? 'site',
            titleTranslationKeyFormat: $options['titleTranslationKeyFormat'] ?? null,
            icon: $options['icon'] ?? null,
            color: $options['color'] ?? null
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];
        return $result;
    };

    $this->createField = function (string $name, string $type = 'craft\\fields\\PlainText', array $options = []) {
        $createField = Craft::$container->get(CreateField::class);

        $result = $createField->create(
            type: $type,
            name: $name,
            handle: $options['handle'] ?? null,
            instructions: $options['instructions'] ?? null,
            searchable: $options['searchable'] ?? true,
            translationMethod: $options['translationMethod'] ?? 'none',
            settings: $options['settings'] ?? []
        );

        $this->createdFieldIds[] = $result['fieldId'];
        return $result;
    };

    $this->getFieldLayout = function (int $entryTypeId) {
        // Get the field layout ID from the entry type
        $entriesService = Craft::$app->getEntries();
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if (!$entryType) {
            throw new \RuntimeException("Entry type with ID {$entryTypeId} not found");
        }

        $fieldLayoutId = $entryType->fieldLayoutId;
        if (!$fieldLayoutId) {
            throw new \RuntimeException("Entry type {$entryTypeId} does not have a field layout");
        }

        $getFieldLayout = Craft::$container->get(GetFieldLayout::class);

        return $getFieldLayout->get($fieldLayoutId);
    };

    $this->updateFieldLayout = function (int $entryTypeId, array $tabs) {
        // Get the field layout ID from the entry type
        $entriesService = Craft::$app->getEntries();
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if (!$entryType) {
            throw new \RuntimeException("Entry type with ID {$entryTypeId} not found");
        }

        $fieldLayoutId = $entryType->fieldLayoutId;
        if (!$fieldLayoutId) {
            throw new \RuntimeException("Entry type {$entryTypeId} does not have a field layout");
        }

        $updateFieldLayout = Craft::$container->get(UpdateFieldLayout::class);

        return $updateFieldLayout->update($fieldLayoutId, $tabs);
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
});

describe('GetFieldLayout', function () {
    test('can retrieve field layout with native title field', function () {
        // Create entry type with title field
        $entryTypeResult = ($this->createEntryType)('Test Entry Type', [
            'handle' => 'testGetFieldLayout',
            'hasTitleField' => true
        ]);
        $entryTypeId = $entryTypeResult['entryTypeId'];

        // Get field layout
        $result = ($this->getFieldLayout)($entryTypeId);

        expect($result)->toHaveKeys(['_notes', 'fieldLayout']);
        expect($result['fieldLayout'])->toHaveKeys(['id', 'type', 'tabs']);
        expect($result['fieldLayout']['tabs'])->toBeArray();
        expect($result['fieldLayout']['tabs'])->toHaveCount(1);

        $tab = $result['fieldLayout']['tabs'][0];
        expect($tab)->toHaveKeys(['name', 'elements']);
        expect($tab['name'])->toBe('Content');
        expect($tab['elements'])->toBeArray();
        expect($tab['elements'])->toHaveCount(1);

        // Verify title field element
        $titleElement = $tab['elements'][0];
        expect($titleElement)->toHaveKey('type');
        expect($titleElement['type'])->toBe('craft\\fieldlayoutelements\\entries\\EntryTitleField');
        expect($titleElement)->toHaveKey('uid');
        expect($titleElement)->toHaveKey('width');
        expect($titleElement)->toHaveKey('attribute');
        expect($titleElement['attribute'])->toBe('title');
    });

    test('validates field layout exists', function () {
        expect(function () {
            $getFieldLayout = Craft::$container->get(GetFieldLayout::class);
            $getFieldLayout->get(999999);
        })->toThrow(\RuntimeException::class, 'Field layout with ID 999999 not found');
    });

    test('returns proper response structure', function () {
        // Create entry type
        $entryTypeResult = ($this->createEntryType)('Test Structure', [
            'handle' => 'testStructure'
        ]);
        $entryTypeId = $entryTypeResult['entryTypeId'];

        $result = ($this->getFieldLayout)($entryTypeId);

        // Verify top-level structure
        expect($result)->toHaveKeys(['_notes', 'fieldLayout']);
        expect($result['_notes'])->toBeString();

        // Verify field layout structure
        $fieldLayout = $result['fieldLayout'];
        expect($fieldLayout)->toHaveKeys(['id', 'type', 'tabs']);
        expect($fieldLayout['id'])->toBeInt();
        expect($fieldLayout['type'])->toBeString();
        expect($fieldLayout['tabs'])->toBeArray();

        // Verify tab structure
        foreach ($fieldLayout['tabs'] as $tab) {
            expect($tab)->toHaveKeys(['name', 'elements']);
            expect($tab['name'])->toBeString();
            expect($tab['elements'])->toBeArray();

            // Verify element structure
            foreach ($tab['elements'] as $element) {
                expect($element)->toHaveKeys(['uid', 'type', 'width']);
                expect($element['uid'])->toBeString();
                expect($element['type'])->toBeString();
                expect($element['width'])->toBeInt();
            }
        }
    });
});

