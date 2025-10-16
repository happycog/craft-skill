<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\UpdateFieldLayout;

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

    test('can retrieve field layout with mixed native and custom fields', function () {
        // Create entry type and custom field
        $entryTypeResult = ($this->createEntryType)('Test Mixed Layout', [
            'handle' => 'testMixedLayout',
            'hasTitleField' => true
        ]);
        $fieldResult = ($this->createField)('Custom Field', 'craft\\fields\\PlainText', [
            'handle' => 'testField1'
        ]);

        $entryTypeId = $entryTypeResult['entryTypeId'];
        $fieldId = $fieldResult['fieldId'];

        // First get the initial layout to preserve the title field
        $initialLayout = ($this->getFieldLayout)($entryTypeId);
        $initialElements = $initialLayout['fieldLayout']['tabs'][0]['elements'];
        
        // Add custom field to layout using elements format (preserving existing title field)
        $tabs = [
            [
                'name' => 'Content',
                'elements' => [
                    // Preserve existing title field
                    ...$initialElements,
                    // Add new custom field
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'width' => 100,
                        'fieldId' => $fieldId,
                        'required' => false,
                    ]
                ]
            ]
        ];
        ($this->updateFieldLayout)($entryTypeId, $tabs);

        // Now get the updated field layout
        $result = ($this->getFieldLayout)($entryTypeId);

        expect($result['fieldLayout']['tabs'])->toHaveCount(1);
        $tab = $result['fieldLayout']['tabs'][0];
        expect($tab['elements'])->toHaveCount(2); // Title + custom field

        // Find title and custom field elements
        $titleElement = null;
        $customElement = null;
        foreach ($tab['elements'] as $element) {
            if ($element['type'] === 'craft\\fieldlayoutelements\\entries\\EntryTitleField') {
                $titleElement = $element;
            } elseif ($element['type'] === 'craft\\fieldlayoutelements\\CustomField') {
                $customElement = $element;
            }
        }

        expect($titleElement)->not->toBeNull();
        expect($customElement)->not->toBeNull();

        // Verify title field properties
        expect($titleElement['attribute'])->toBe('title');
        expect($titleElement)->toHaveKey('uid');

        // Verify custom field properties
        expect($customElement['fieldId'])->toBe($fieldId);
        expect($customElement)->toHaveKey('fieldName');
        expect($customElement)->toHaveKey('fieldHandle');
        expect($customElement)->toHaveKey('uid');
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

describe('Field Layout Preservation Workflow', function () {
    test('preserves native title field when updating field layout using elements format', function () {
        // Create entry type with title field and custom field
        $entryTypeResult = ($this->createEntryType)('Test Preservation', [
            'handle' => 'testPreservation',
            'hasTitleField' => true
        ]);
        $fieldResult = ($this->createField)('Additional Field', 'craft\\fields\\PlainText', [
            'handle' => 'testField2'
        ]);

        $entryTypeId = $entryTypeResult['entryTypeId'];
        $fieldId = $fieldResult['fieldId'];

        // First get the initial layout to preserve the title field
        $initialLayout = ($this->getFieldLayout)($entryTypeId);
        $initialElements = $initialLayout['fieldLayout']['tabs'][0]['elements'];
        
        // First, add a custom field using elements format
        $tabs = [
            [
                'name' => 'Content',
                'elements' => [
                    // Preserve existing title field
                    ...$initialElements,
                    // Add new custom field
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'width' => 100,
                        'fieldId' => $fieldId,
                        'required' => false,
                    ]
                ]
            ]
        ];
        ($this->updateFieldLayout)($entryTypeId, $tabs);

        // Get the current field layout (should have title + custom field)
        $currentLayout = ($this->getFieldLayout)($entryTypeId);
        expect($currentLayout['fieldLayout']['tabs'][0]['elements'])->toHaveCount(2);

        // Find the title element and custom field element
        $elements = $currentLayout['fieldLayout']['tabs'][0]['elements'];
        $titleElement = null;
        $customElement = null;
        foreach ($elements as $element) {
            if ($element['type'] === 'craft\\fieldlayoutelements\\entries\\EntryTitleField') {
                $titleElement = $element;
            } elseif ($element['type'] === 'craft\\fieldlayoutelements\\CustomField') {
                $customElement = $element;
            }
        }

        expect($titleElement)->not->toBeNull();
        expect($customElement)->not->toBeNull();

        // Now create a second custom field and update using elements format
        $field2Result = ($this->createField)('Second Field', 'craft\\fields\\PlainText', [
            'handle' => 'secondField'
        ]);
        $field2Id = $field2Result['fieldId'];

        // Prepare new elements array that includes existing title + custom field + new field
        $newElementsTab = [
            'name' => 'Content',
            'elements' => [
                // Preserve existing title field
                [
                    'uid' => $titleElement['uid'],
                    'type' => $titleElement['type'],
                    'width' => $titleElement['width'],
                    'attribute' => $titleElement['attribute'],
                    'required' => $titleElement['required'] ?? false,
                ],
                // Preserve existing custom field
                [
                    'uid' => $customElement['uid'],
                    'type' => $customElement['type'],
                    'width' => $customElement['width'],
                    'fieldId' => $customElement['fieldId'],
                    'required' => $customElement['required'] ?? false,
                ],
                // Add new custom field
                [
                    'type' => 'craft\\fieldlayoutelements\\CustomField',
                    'width' => 50,
                    'fieldId' => $field2Id,
                    'required' => true,
                ]
            ]
        ];

        // Update using elements format
        $updateResult = ($this->updateFieldLayout)($entryTypeId, [$newElementsTab]);

        // Verify the update worked and preserved all elements
        expect($updateResult['fieldLayout']['tabs'])->toHaveCount(1);
        $updatedTab = $updateResult['fieldLayout']['tabs'][0];

        // Should have all 3 elements: title + first custom field + second custom field
        expect($updatedTab['elements'])->toHaveCount(3);

        // Verify we still have the title field
        $hasTitle = false;
        $hasFirstCustom = false;
        $hasSecondCustom = false;

        foreach ($updatedTab['elements'] as $element) {
            if ($element['type'] === 'craft\\fieldlayoutelements\\entries\\EntryTitleField') {
                $hasTitle = true;
                expect($element['attribute'])->toBe('title');
            } elseif ($element['type'] === 'craft\\fieldlayoutelements\\CustomField') {
                if ($element['fieldId'] === $fieldId) {
                    $hasFirstCustom = true;
                } elseif ($element['fieldId'] === $field2Id) {
                    $hasSecondCustom = true;
                    expect($element['required'])->toBe(true);
                    expect($element['width'])->toBe(50);
                }
            }
        }

        expect($hasTitle)->toBe(true);
        expect($hasFirstCustom)->toBe(true);
        expect($hasSecondCustom)->toBe(true);
    });

    test('workflow: get_field_layout → modify → update_field_layout preserves all elements', function () {
        // Create entry type with title field
        $entryTypeResult = ($this->createEntryType)('Test Full Workflow', [
            'handle' => 'testFullWorkflow',
            'hasTitleField' => true
        ]);
        $entryTypeId = $entryTypeResult['entryTypeId'];

        // Step 1: Get initial field layout (should have title field)
        $initialLayout = ($this->getFieldLayout)($entryTypeId);
        expect($initialLayout['fieldLayout']['tabs'][0]['elements'])->toHaveCount(1);

        // Step 2: Add a custom field using elements format
        $fieldResult = ($this->createField)('Workflow Field', 'craft\\fields\\PlainText', [
            'handle' => 'workflowField'
        ]);
        $fieldId = $fieldResult['fieldId'];

        $elementsTabs = [
            [
                'name' => 'Content',
                'elements' => [
                    // Preserve existing title field
                    ...$initialLayout['fieldLayout']['tabs'][0]['elements'],
                    // Add new custom field
                    [
                        'type' => 'craft\\fieldlayoutelements\\CustomField',
                        'width' => 100,
                        'fieldId' => $fieldId,
                        'required' => false,
                    ]
                ]
            ]
        ];
        ($this->updateFieldLayout)($entryTypeId, $elementsTabs);

        // Step 3: Get current layout (should have title + custom field)
        $currentLayout = ($this->getFieldLayout)($entryTypeId);
        expect($currentLayout['fieldLayout']['tabs'][0]['elements'])->toHaveCount(2);

        // Step 4: Modify the layout while preserving existing elements
        $tabs = $currentLayout['fieldLayout']['tabs'];
        
        // Modify the custom field to be required
        foreach ($tabs[0]['elements'] as &$element) {
            if ($element['type'] === 'craft\\fieldlayoutelements\\CustomField') {
                $element['required'] = true;
                $element['width'] = 75;
            }
        }
        unset($element); // Break the reference

        // Step 5: Update using the modified structure
        $finalResult = ($this->updateFieldLayout)($entryTypeId, $tabs);

        // Step 6: Verify all elements are preserved with modifications
        expect($finalResult['fieldLayout']['tabs'][0]['elements'])->toHaveCount(2);

        $finalElements = $finalResult['fieldLayout']['tabs'][0]['elements'];
        $titlePreserved = false;
        $customFieldModified = false;

        foreach ($finalElements as $element) {
            if ($element['type'] === 'craft\\fieldlayoutelements\\entries\\EntryTitleField') {
                $titlePreserved = true;
                expect($element['attribute'])->toBe('title');
            } elseif ($element['type'] === 'craft\\fieldlayoutelements\\CustomField') {
                $customFieldModified = true;
                expect($element['required'])->toBe(true);
                expect($element['width'])->toBe(75);
                expect($element['fieldId'])->toBe($fieldId);
            }
        }

        expect($titlePreserved)->toBe(true);
        expect($customFieldModified)->toBe(true);
    });
});