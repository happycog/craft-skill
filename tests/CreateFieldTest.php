<?php

use happycog\craftmcp\tools\CreateField;

beforeEach(function () {
    // Clean up any existing test fields before each test
    $fieldsService = Craft::$app->getFields();
    $testHandles = [
        'testField', 'customHandle', 'instructionalField', 'settingsField', 
        'duplicateHandle', 'complexFieldNameWithCharacters', 'field123NumericField',
        'urlTestField', 'translationField'
    ];
    
    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }
    
    // Track created fields for cleanup
    $this->createdFieldIds = [];
    
    $this->createField = function (string $type, string $name, array $options = []) {
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
        
        // Track the created field for cleanup
        $this->createdFieldIds[] = $result['fieldId'];
        
        return $result;
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

it('can create a plain text field', function () {
    $result = ($this->createField)('craft\fields\PlainText', 'Test Field');
    
    expect($result)->toHaveKeys(['fieldId', 'name', 'handle', 'type', 'editUrl']);
    expect($result['name'])->toBe('Test Field');
    expect($result['handle'])->toBe('testField');
    expect($result['type'])->toBe('craft\fields\PlainText');
    
    // Verify field was actually created
    $field = Craft::$app->getFields()->getFieldById($result['fieldId']);
    expect($field)->not->toBeNull();
    expect($field->name)->toBe('Test Field');
    expect($field->handle)->toBe('testField');
});

it('can create a field with custom handle', function () {
    $result = ($this->createField)(
        'craft\fields\PlainText', 
        'Custom Field',
        ['handle' => 'customHandle']
    );
    
    expect($result['handle'])->toBe('customHandle');
    
    $field = Craft::$app->getFields()->getFieldById($result['fieldId']);
    expect($field->handle)->toBe('customHandle');
});

it('can create a field with instructions', function () {
    $instructions = 'This is a test field with instructions';
    $result = ($this->createField)(
        'craft\fields\PlainText',
        'Instructional Field',
        ['instructions' => $instructions]
    );
    
    expect($result['instructions'])->toBe($instructions);
    
    $field = Craft::$app->getFields()->getFieldById($result['fieldId']);
    expect($field->instructions)->toBe($instructions);
});

it('can create a field with custom settings', function () {
    $settings = [
        'placeholder' => 'Enter text here',
        'charLimit' => 100,
        'multiline' => false
    ];
    
    $result = ($this->createField)(
        'craft\fields\PlainText',
        'Settings Field',
        ['settings' => $settings]
    );
    
    $field = Craft::$app->getFields()->getFieldById($result['fieldId']);
    expect($field->placeholder)->toBe('Enter text here');
    expect($field->charLimit)->toBe(100);
    expect($field->multiline)->toBeFalse();
});

it('throws exception for invalid field type', function () {
    $createField = Craft::$container->get(CreateField::class);
    
    expect(fn() => $createField->create(
        type: 'InvalidFieldType',
        name: 'Test Field'
    ))->toThrow(InvalidArgumentException::class, "Field type 'InvalidFieldType' is not available.");
});

it('throws exception for duplicate handle', function () {
    // Create first field
    ($this->createField)('craft\fields\PlainText', 'First Field', ['handle' => 'duplicateHandle']);
    
    // Try to create second field with same handle
    expect(fn() => ($this->createField)(
        'craft\fields\PlainText',
        'Second Field',
        ['handle' => 'duplicateHandle']
    ))->toThrow(InvalidArgumentException::class, "A field with handle 'duplicateHandle' already exists.");
});

it('generates valid handle from field name', function () {
    $result = ($this->createField)('craft\fields\PlainText', 'Complex Field Name! With @#$% Characters');
    
    expect($result['handle'])->toBe('complexFieldNameWithCharacters');
});

it('handles field names starting with numbers', function () {
    $result = ($this->createField)('craft\fields\PlainText', '123 Numeric Field');
    
    expect($result['handle'])->toBe('field123NumericField');
});

it('includes control panel edit URL', function () {
    $result = ($this->createField)('craft\fields\PlainText', 'URL Test Field');
    
    expect($result['editUrl'])->toContain('/settings/fields/edit/');
    expect($result['editUrl'])->toContain((string)$result['fieldId']);
});

it('throws exception for invalid translation method', function () {
    expect(fn() => ($this->createField)(
        'craft\fields\PlainText',
        'Translation Test',
        ['translationMethod' => 'invalid']
    ))->toThrow(InvalidArgumentException::class, "Invalid translation method 'invalid'");
});

it('can create field with different translation methods', function () {
    $result = ($this->createField)(
        'craft\fields\PlainText',
        'Translation Field',
        ['translationMethod' => 'site']
    );
    
    $field = Craft::$app->getFields()->getFieldById($result['fieldId']);
    expect($field->translationMethod)->toBe(\craft\base\Field::TRANSLATION_METHOD_SITE);
});