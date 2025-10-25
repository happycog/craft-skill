<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\AddFieldToFieldLayout;

test('complete workflow: create matrix field with nested fields', function () {
    // Step 1: Create entry types that will serve as Matrix block types
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    
    $textBlockType = $createEntryType->create(
        name: 'Text Block',
        handle: 'textBlock',
        description: 'A simple text content block'
    );
    
    $imageBlockType = $createEntryType->create(
        name: 'Image Block',
        handle: 'imageBlock',
        description: 'An image with optional caption'
    );
    
    // Verify entry types were created with UIDs
    expect($textBlockType)->toHaveKeys(['entryTypeId', 'uid', 'name', 'handle']);
    expect($imageBlockType)->toHaveKeys(['entryTypeId', 'uid', 'name', 'handle']);
    
    // Step 2: Create fields to add to the Matrix block layouts
    $createField = Craft::$container->get(CreateField::class);
    
    $textField = $createField->create(
        type: 'craft\fields\PlainText',
        name: 'Text Content',
        handle: 'textContent',
        settings: [
            'multiline' => true,
            'charLimit' => 1000,
            'placeholder' => 'Enter your text content here...',
        ]
    );
    
    $captionField = $createField->create(
        type: 'craft\fields\PlainText',
        name: 'Caption',
        handle: 'caption',
        settings: [
            'multiline' => false,
            'charLimit' => 200,
            'placeholder' => 'Enter image caption...',
        ]
    );
    
    // Step 3: Add fields to the entry type field layouts
    $addFieldToLayout = Craft::$container->get(AddFieldToFieldLayout::class);
    
    // Add text field to text block layout
    $addFieldToLayout->add(
        fieldLayoutId: $textBlockType['fieldLayoutId'],
        fieldId: $textField['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append'],
        required: true
    );
    
    // Add caption field to image block layout
    $addFieldToLayout->add(
        fieldLayoutId: $imageBlockType['fieldLayoutId'],
        fieldId: $captionField['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append'],
        required: false
    );
    
    // Step 4: Create the Matrix field with both block types
    $matrixField = $createField->create(
        type: 'craft\fields\Matrix',
        name: 'Flexible Content',
        handle: 'flexibleContent',
        instructions: 'Add flexible content blocks of text and images',
        settings: [
            'minEntries' => 1,
            'maxEntries' => 20,
            'viewMode' => 'cards',
            'showCardsInGrid' => true,
            'createButtonLabel' => 'Add Content Block',
            'entryTypes' => [
                ['uid' => $textBlockType['uid']],
                ['uid' => $imageBlockType['uid']],
            ],
        ]
    );
    
    // Verify the Matrix field was created successfully
    expect($matrixField)->toHaveKeys(['fieldId', 'name', 'handle', 'type', 'editUrl']);
    expect($matrixField['type'])->toBe('craft\fields\Matrix');
    expect($matrixField['handle'])->toBe('flexibleContent');
    
    // Verify the Matrix field has the correct configuration
    $field = Craft::$app->getFields()->getFieldById($matrixField['fieldId']);
    expect($field)->toBeInstanceOf(\craft\fields\Matrix::class);
    expect($field->minEntries)->toBe(1);
    expect($field->maxEntries)->toBe(20);
    expect($field->viewMode)->toBe('cards');
    expect($field->showCardsInGrid)->toBeTrue();
    expect($field->createButtonLabel)->toBe('Add Content Block');
    
    // Verify both block types are attached
    $entryTypes = $field->getEntryTypes();
    expect($entryTypes)->toHaveCount(2);
    
    // Verify the block types have their fields
    $textBlock = collect($entryTypes)->first(fn($et) => $et->handle === 'textBlock');
    $imageBlock = collect($entryTypes)->first(fn($et) => $et->handle === 'imageBlock');
    
    expect($textBlock)->not->toBeNull();
    expect($imageBlock)->not->toBeNull();
    
    $textBlockFields = $textBlock->getFieldLayout()->getCustomFields();
    $imageBlockFields = $imageBlock->getFieldLayout()->getCustomFields();
    
    expect($textBlockFields)->toHaveCount(1);
    expect($textBlockFields[0]->handle)->toBe('textContent');
    
    expect($imageBlockFields)->toHaveCount(1);
    expect($imageBlockFields[0]->handle)->toBe('caption');
    
    // Clean up
    $fieldsService = Craft::$app->getFields();
    $entriesService = Craft::$app->getEntries();
    
    $fieldsService->deleteField($field);
    $fieldsService->deleteField($fieldsService->getFieldById($textField['fieldId']));
    $fieldsService->deleteField($fieldsService->getFieldById($captionField['fieldId']));
    $entriesService->deleteEntryType($textBlock);
    $entriesService->deleteEntryType($imageBlock);
});
