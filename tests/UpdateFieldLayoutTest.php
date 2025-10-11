<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\UpdateFieldLayout;

beforeEach(function () {
    // Clean up any existing test data
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();
    
    $testHandles = ['testUpdateFieldLayout', 'testField1', 'testField2', 'testField3'];
    
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
    
    $this->updateFieldLayout = function (int $entryTypeId, array $tabs) {
        $updateFieldLayout = Craft::$container->get(UpdateFieldLayout::class);
        
        return $updateFieldLayout->update($entryTypeId, $tabs);
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

describe('UpdateFieldLayout', function () {
    test('can update field layout with single tab', function () {
        // Create entry type and fields
        $entryTypeResult = ($this->createEntryType)('Test Entry Type', ['handle' => 'testUpdateFieldLayout']);
        $field1Result = ($this->createField)('Test Field 1', 'craft\\fields\\PlainText', ['handle' => 'testField1']);
        $field2Result = ($this->createField)('Test Field 2', 'craft\\fields\\PlainText', ['handle' => 'testField2']);
        
        $entryTypeId = $entryTypeResult['entryTypeId'];
        $field1Id = $field1Result['fieldId'];
        $field2Id = $field2Result['fieldId'];
        
        // Update field layout
        $tabs = [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => $field1Id, 'required' => true, 'width' => 100],
                    ['fieldId' => $field2Id, 'required' => false, 'width' => 50],
                ]
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        expect($result)->toHaveKey('entryType');
        expect($result)->toHaveKey('fieldLayout');
        expect($result['entryType']['id'])->toBe($entryTypeId);
        expect($result['fieldLayout']['tabs'])->toHaveCount(1);
        
        $contentTab = $result['fieldLayout']['tabs'][0];
        expect($contentTab['name'])->toBe('Content');
        expect($contentTab['fields'])->toHaveCount(2);
        
        $firstField = $contentTab['fields'][0];
        expect($firstField['id'])->toBe($field1Id);
        expect($firstField['required'])->toBe(true);
        expect($firstField['width'])->toBe(100);
        
        $secondField = $contentTab['fields'][1];
        expect($secondField['id'])->toBe($field2Id);
        expect($secondField['required'])->toBe(false);
        expect($secondField['width'])->toBe(50);
    });
    
    test('can update field layout with multiple tabs', function () {
        // Create entry type and fields
        $entryTypeResult = ($this->createEntryType)('Test Entry Type Multi', ['handle' => 'testUpdateFieldLayoutMulti']);
        $field1Result = ($this->createField)('Test Field 1', 'craft\\fields\\PlainText', ['handle' => 'testField1Multi']);
        $field2Result = ($this->createField)('Test Field 2', 'craft\\fields\\PlainText', ['handle' => 'testField2Multi']);
        $field3Result = ($this->createField)('Test Field 3', 'craft\\fields\\PlainText', ['handle' => 'testField3Multi']);
        
        $entryTypeId = $entryTypeResult['entryTypeId'];
        $field1Id = $field1Result['fieldId'];
        $field2Id = $field2Result['fieldId'];
        $field3Id = $field3Result['fieldId'];
        
        // Update field layout with multiple tabs
        $tabs = [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => $field1Id, 'required' => true, 'width' => 100],
                ]
            ],
            [
                'name' => 'Metadata',
                'fields' => [
                    ['fieldId' => $field2Id, 'required' => false, 'width' => 50],
                    ['fieldId' => $field3Id, 'required' => true, 'width' => 50],
                ]
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        expect($result)->toHaveKey('entryType');
        expect($result)->toHaveKey('fieldLayout');
        expect($result['fieldLayout']['tabs'])->toHaveCount(2);
        
        $contentTab = $result['fieldLayout']['tabs'][0];
        expect($contentTab['name'])->toBe('Content');
        expect($contentTab['fields'])->toHaveCount(1);
        expect($contentTab['fields'][0]['id'])->toBe($field1Id);
        
        $metadataTab = $result['fieldLayout']['tabs'][1];
        expect($metadataTab['name'])->toBe('Metadata');
        expect($metadataTab['fields'])->toHaveCount(2);
        expect($metadataTab['fields'][0]['id'])->toBe($field2Id);
        expect($metadataTab['fields'][1]['id'])->toBe($field3Id);
    });
    
    test('can update field layout with empty tabs', function () {
        // Create entry type
        $entryTypeResult = ($this->createEntryType)('Test Entry Type Empty', ['handle' => 'testUpdateFieldLayoutEmpty']);
        $entryTypeId = $entryTypeResult['entryTypeId'];
        
        // Update field layout with empty tabs
        $tabs = [
            [
                'name' => 'Empty Tab',
                'fields' => []
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        expect($result)->toHaveKey('entryType');
        expect($result)->toHaveKey('fieldLayout');
        expect($result['fieldLayout']['tabs'])->toHaveCount(1);
        
        $emptyTab = $result['fieldLayout']['tabs'][0];
        expect($emptyTab['name'])->toBe('Empty Tab');
        expect($emptyTab['fields'])->toHaveCount(0);
    });
    
    test('validates required entry type ID', function () {
        expect(fn() => ($this->updateFieldLayout)(999999, []))
            ->toThrow(\RuntimeException::class, 'Entry type with ID 999999 not found');
    });
    
    test('validates field IDs exist', function () {
        // Create entry type
        $entryTypeResult = ($this->createEntryType)('Test Entry Type Validation', ['handle' => 'testUpdateFieldLayoutValidation']);
        $entryTypeId = $entryTypeResult['entryTypeId'];
        
        // Try to update with non-existent field
        $tabs = [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => 999999, 'required' => false, 'width' => 100],
                ]
            ]
        ];
        
        expect(fn() => ($this->updateFieldLayout)($entryTypeId, $tabs))
            ->toThrow(\RuntimeException::class, 'Field with ID 999999 not found');
    });
    
    test('handles different field width values', function () {
        // Create entry type and fields
        $entryTypeResult = ($this->createEntryType)('Test Entry Type Width', ['handle' => 'testUpdateFieldLayoutWidth']);
        $field1Result = ($this->createField)('Test Field Width 1', 'craft\\fields\\PlainText', ['handle' => 'testFieldWidth1']);
        $field2Result = ($this->createField)('Test Field Width 2', 'craft\\fields\\PlainText', ['handle' => 'testFieldWidth2']);
        $field3Result = ($this->createField)('Test Field Width 3', 'craft\\fields\\PlainText', ['handle' => 'testFieldWidth3']);
        $field4Result = ($this->createField)('Test Field Width 4', 'craft\\fields\\PlainText', ['handle' => 'testFieldWidth4']);
        
        $entryTypeId = $entryTypeResult['entryTypeId'];
        $field1Id = $field1Result['fieldId'];
        $field2Id = $field2Result['fieldId'];
        $field3Id = $field3Result['fieldId'];
        $field4Id = $field4Result['fieldId'];
        
        // Update field layout with different widths
        $tabs = [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => $field1Id, 'required' => false, 'width' => 25],
                    ['fieldId' => $field2Id, 'required' => false, 'width' => 50],
                    ['fieldId' => $field3Id, 'required' => false, 'width' => 75],
                    ['fieldId' => $field4Id, 'required' => false, 'width' => 100],
                ]
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        $fields = $result['fieldLayout']['tabs'][0]['fields'];
        expect($fields[0]['width'])->toBe(25);
        expect($fields[1]['width'])->toBe(50);
        expect($fields[2]['width'])->toBe(75);
        expect($fields[3]['width'])->toBe(100);
    });
    
    test('includes edit URL when entry type is associated with a section', function () {
        // Create entry type (which won't be associated with a section by default)
        $entryTypeResult = ($this->createEntryType)('Test Entry Type URL', ['handle' => 'testUpdateFieldLayoutURL']);
        $entryTypeId = $entryTypeResult['entryTypeId'];
        
        // Update field layout with empty tabs
        $tabs = [
            [
                'name' => 'Content',
                'fields' => []
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        // Since entry types created via CreateEntryType are standalone (not associated with sections),
        // the edit URL should be null
        expect($result['editUrl'])->toBeNull();
    });
    
    test('includes proper response structure', function () {
        // Create entry type and field
        $entryTypeResult = ($this->createEntryType)('Test Entry Type Structure', ['handle' => 'testUpdateFieldLayoutStructure']);
        $fieldResult = ($this->createField)('Test Field Structure', 'craft\\fields\\PlainText', ['handle' => 'testFieldStructure']);
        
        $entryTypeId = $entryTypeResult['entryTypeId'];
        $fieldId = $fieldResult['fieldId'];
        
        // Update field layout
        $tabs = [
            [
                'name' => 'Content',
                'fields' => [
                    ['fieldId' => $fieldId, 'required' => true, 'width' => 100],
                ]
            ]
        ];
        
        $result = ($this->updateFieldLayout)($entryTypeId, $tabs);
        
        // Verify response structure
        expect($result)->toHaveKeys(['_notes', 'entryType', 'fieldLayout', 'editUrl']);
        expect($result['_notes'])->toBeArray();
        expect($result['entryType'])->toHaveKeys(['id', 'name', 'handle', 'fieldLayoutId']);
        expect($result['fieldLayout'])->toHaveKeys(['id', 'type', 'tabs']);
        expect($result['fieldLayout']['tabs'])->toBeArray();
        
        // Verify field structure within tab
        $field = $result['fieldLayout']['tabs'][0]['fields'][0];
        expect($field)->toHaveKeys(['id', 'name', 'handle', 'type', 'required', 'width']);
    });
});