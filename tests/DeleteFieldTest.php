<?php

use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\DeleteField;

beforeEach(function () {
    // Clean up any existing test fields before each test
    $fieldsService = Craft::$app->getFields();
    $testHandles = ['testField', 'deletionTest', 'warningTest'];
    
    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }
    
    // Track created fields for cleanup
    $this->createdFieldIds = [];
    
    // Helper to create a test field
    $this->createTestField = function (string $name = 'Test Field', array $options = []) {
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
    
    // Helper to delete a field
    $this->deleteField = function (int $fieldId) {
        $deleteField = Craft::$container->get(DeleteField::class);
        
        return $deleteField->delete(
            fieldId: $fieldId
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

it('can delete a field', function () {
    $created = ($this->createTestField)();
    $result = ($this->deleteField)($created['fieldId']);
    
    expect($result['success'])->toBeTrue();
    expect($result['deletedField']['id'])->toBe($created['fieldId']);
    expect($result['deletedField']['name'])->toBe($created['name']);
    expect($result['deletedField']['handle'])->toBe($created['handle']);
    
    // Verify field is deleted
    $field = Craft::$app->getFields()->getFieldById($created['fieldId']);
    expect($field)->toBeNull();
});


it('returns field information before deletion', function () {
    $created = ($this->createTestField)('Test Field for Deletion', [
        'handle' => 'deletionTest',
        'instructions' => 'Test instructions'
    ]);
    
    $result = ($this->deleteField)($created['fieldId']);
    
    expect($result['deletedField'])->toHaveKeys(['id', 'name', 'handle', 'type', 'usages', 'usageCount']);
    expect($result['deletedField']['name'])->toBe('Test Field for Deletion');
    expect($result['deletedField']['handle'])->toBe('deletionTest');
    expect($result['deletedField']['type'])->toBe('craft\fields\PlainText');
});

it('includes usage information', function () {
    $created = ($this->createTestField)();
    $result = ($this->deleteField)($created['fieldId']);
    
    expect($result['deletedField'])->toHaveKey('usages');
    expect($result['deletedField'])->toHaveKey('usageCount');
    expect($result['affectedLayouts'])->toBe($result['deletedField']['usageCount']);
});

it('throws exception for non-existent field', function () {
    expect(fn() => ($this->deleteField)(99999))
        ->toThrow(InvalidArgumentException::class, 'Field with ID 99999 does not exist.');
});

it('includes appropriate warning messages', function () {
    $created = ($this->createTestField)('Warning Test Field', ['handle' => 'warningTest']);
    
    $result = ($this->deleteField)($created['fieldId']);
    expect($result['_notes'])->toContain('permanently deleted');
    expect($result['_notes'])->toContain('cannot be restored');
});

it('warns about content loss when field has usages', function () {
    $created = ($this->createTestField)();
    $result = ($this->deleteField)($created['fieldId']);
    
    // Always assert the usage count is available
    expect($result['deletedField'])->toHaveKey('usageCount');
    expect($result['deletedField']['usageCount'])->toBeInt();
    
    // The warning message should mention affected layouts if there are any
    if ($result['deletedField']['usageCount'] > 0) {
        expect($result['_notes'])->toContain('layout(s)');
        expect($result['_notes'])->toContain('content has been removed');
    } else {
        // Even with no usages, assert that the deletion was successful
        expect($result['success'])->toBeTrue();
        expect($result['_notes'])->toContain('permanently deleted');
    }
});

it('returns correct success status', function () {
    $created = ($this->createTestField)();
    $result = ($this->deleteField)($created['fieldId']);
    
    expect($result['success'])->toBeTrue();
});

it('handles field type information correctly', function () {
    $created = ($this->createTestField)();
    $result = ($this->deleteField)($created['fieldId']);
    
    expect($result['deletedField']['type'])->toBe('craft\fields\PlainText');
});