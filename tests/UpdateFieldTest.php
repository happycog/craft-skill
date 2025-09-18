<?php

use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\UpdateField;

beforeEach(function () {
    // Clean up any existing test fields before each test
    $fieldsService = Craft::$app->getFields();
    $testHandles = [
        'testField', 'originalHandle', 'updatedHandle', 'field1', 'field2',
        'instructionsTest', 'searchableTest', 'settingsTest', 'translationTest',
        'multiUpdate', 'updatedMulti', 'preserveSettingsTest'
    ];
    
    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }
    
    // Track created fields for cleanup
    $this->createdFieldIds = [];
    
    // Helper to create a test field
    $this->createTestField = function (?string $name = null, array $options = []) {
        $name = $name ?: 'Test Field';
        $createField = Craft::$container->get(CreateField::class);
        
        $result = $createField->create(
            type: 'craft\fields\PlainText',
            name: $name,
            handle: $options['handle'] ?? 'testField',
            instructions: $options['instructions'] ?? null,
            searchable: $options['searchable'] ?? true,
            translationMethod: $options['translationMethod'] ?? 'none',
            settings: $options['settings'] ?? []
        );
        
        // Track the created field for cleanup
        $this->createdFieldIds[] = $result['fieldId'];
        
        return $result;
    };
    
    // Helper to update a field
    $this->updateField = function (int $fieldId, array $updates = []) {
        $updateField = Craft::$container->get(UpdateField::class);
        
        return $updateField->update(
            fieldId: $fieldId,
            name: $updates['name'] ?? null,
            handle: $updates['handle'] ?? null,
            instructions: $updates['instructions'] ?? null,
            searchable: $updates['searchable'] ?? null,
            translationMethod: $updates['translationMethod'] ?? null,
            settings: $updates['settings'] ?? null,
            type: $updates['type'] ?? null
        );
    };
});

afterEach(function () {
    // Clean up any fields that weren't deleted during the test
    $fieldsService = Craft::$app->getFields();
    
    foreach ($this->createdFieldIds ?? [] as $fieldId) {
        $field = $fieldsService->getFieldById($fieldId);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }
});

it('can update field name', function () {
    $created = ($this->createTestField)('Original Name');
    $result = ($this->updateField)($created['fieldId'], ['name' => 'Updated Name']);
    
    expect($result['name'])->toBe('Updated Name');
    expect($result['changes'])->toContain("name: 'Original Name' → 'Updated Name'");
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->name)->toBe('Updated Name');
});

it('can update field handle', function () {
    $created = ($this->createTestField)('Original Handle Test', ['handle' => 'originalHandle']);
    $result = ($this->updateField)($created['fieldId'], ['handle' => 'updatedHandle']);
    
    expect($result['handle'])->toBe('updatedHandle');
    expect($result['changes'])->toContain("handle: 'originalHandle' → 'updatedHandle'");
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->handle)->toBe('updatedHandle');
});

it('can update field instructions', function () {
    $created = ($this->createTestField)('Instructions Test', [
        'handle' => 'instructionsTest',
        'instructions' => 'Original instructions'
    ]);
    $result = ($this->updateField)($created['fieldId'], ['instructions' => 'Updated instructions']);
    
    expect($result['instructions'])->toBe('Updated instructions');
    expect($result['changes'])->toContain('instructions updated');
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->instructions)->toBe('Updated instructions');
});

it('can update searchable property', function () {
    $created = ($this->createTestField)('Searchable Test', [
        'handle' => 'searchableTest',
        'searchable' => true
    ]);
    $result = ($this->updateField)($created['fieldId'], ['searchable' => false]);
    
    expect($result['searchable'])->toBeFalse();
    expect($result['changes'])->toContain('searchable: true → false');
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->searchable)->toBeFalse();
});

it('can update field settings', function () {
    $created = ($this->createTestField)('Settings Test', [
        'handle' => 'settingsTest',
        'settings' => ['placeholder' => 'Original']
    ]);
    $newSettings = ['placeholder' => 'Updated', 'charLimit' => 100];
    $result = ($this->updateField)($created['fieldId'], ['settings' => $newSettings]);
    
    expect($result['changes'])->toContain('settings updated');
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->placeholder)->toBe('Updated');
    expect($field->charLimit)->toBe(100);
});

it('can update translation method', function () {
    $created = ($this->createTestField)('Translation Test', [
        'handle' => 'translationTest',
        'translationMethod' => 'none'
    ]);
    $result = ($this->updateField)($created['fieldId'], ['translationMethod' => 'site']);
    
    expect($result['changes'])->toContain('translation method updated');
    
    // Verify in database
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->translationMethod)->toBe(\craft\base\Field::TRANSLATION_METHOD_SITE);
});

it('can update multiple properties at once', function () {
    $created = ($this->createTestField)('Multi Update Test', ['handle' => 'multiUpdate']);
    $result = ($this->updateField)($created['fieldId'], [
        'name' => 'Updated Multi Name',
        'handle' => 'updatedMulti',
        'instructions' => 'New instructions'
    ]);
    
    expect($result['name'])->toBe('Updated Multi Name');
    expect($result['handle'])->toBe('updatedMulti');
    expect($result['instructions'])->toBe('New instructions');
    expect(count($result['changes']))->toBe(3);
});

it('returns no changes when no updates are made', function () {
    $created = ($this->createTestField)();
    $result = ($this->updateField)($created['fieldId'], []);
    
    expect($result['changes'])->toBeEmpty();
    expect($result['_notes'])->toContain('Changes: none');
});

it('throws exception for non-existent field', function () {
    expect(fn() => ($this->updateField)(99999, ['name' => 'Updated']))
        ->toThrow(InvalidArgumentException::class, 'Field with ID 99999 does not exist.');
});

it('throws exception for duplicate handle', function () {
    $field1 = ($this->createTestField)('Field 1', ['handle' => 'field1']);
    $field2 = ($this->createTestField)('Field 2', ['handle' => 'field2']);
    
    expect(fn() => ($this->updateField)($field2['fieldId'], ['handle' => 'field1']))
        ->toThrow(InvalidArgumentException::class, "A field with handle 'field1' already exists.");
});

it('throws exception for invalid field type', function () {
    $created = ($this->createTestField)();
    
    expect(fn() => ($this->updateField)($created['fieldId'], ['type' => 'InvalidFieldType']))
        ->toThrow(InvalidArgumentException::class, "Field type 'InvalidFieldType' is not available.");
});

it('throws exception for invalid translation method', function () {
    $created = ($this->createTestField)();
    
    expect(fn() => ($this->updateField)($created['fieldId'], ['translationMethod' => 'invalid']))
        ->toThrow(InvalidArgumentException::class, "Invalid translation method 'invalid'");
});

it('includes control panel edit URL', function () {
    $created = ($this->createTestField)();
    $result = ($this->updateField)($created['fieldId'], ['name' => 'Updated Name']);
    
    expect($result['editUrl'])->toContain('/settings/fields/edit/');
    expect($result['editUrl'])->toContain((string)$created['fieldId']);
});

it('preserves existing settings when updating only some settings', function () {
    $created = ($this->createTestField)('Preserve Settings Test', [
        'handle' => 'preserveSettingsTest',
        'settings' => ['placeholder' => 'Original', 'charLimit' => 50]
    ]);
    
    $result = ($this->updateField)($created['fieldId'], [
        'settings' => ['placeholder' => 'Updated']
    ]);
    
    // Verify in database - should merge settings
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field->placeholder)->toBe('Updated');
    expect($field->charLimit)->toBe(50); // Should be preserved
});