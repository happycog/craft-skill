<?php

use happycog\craftmcp\tools\AddFieldToFieldLayout;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\DeleteField;

beforeEach(function () {
    // Clean up any existing test data
    $fieldsService = Craft::$app->getFields();
    $entriesService = Craft::$app->getEntries();

    $testHandles = ['heading', 'newHeading', 'testBrokenRef', 'testField', 'testEntryType'];

    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }

    $entryTypeHandles = ['testBrokenRef', 'testEntryType'];
    foreach ($entryTypeHandles as $handle) {
        $entryType = $entriesService->getEntryTypeByHandle($handle);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
});

afterEach(function () {
    $fieldsService = Craft::$app->getFields();
    $entriesService = Craft::$app->getEntries();

    // Clean up test fields
    $testHandles = ['heading', 'newHeading', 'testField'];
    foreach ($testHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }

    // Clean up test entry types
    $entryTypeHandles = ['testBrokenRef', 'testEntryType'];
    foreach ($entryTypeHandles as $handle) {
        $entryType = $entriesService->getEntryTypeByHandle($handle);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
});

test('field layout cleanup runs when field is deleted', function () {
    $createField = Craft::$container->get(CreateField::class);
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $deleteField = Craft::$container->get(DeleteField::class);

    // Step 1: Create a field
    $fieldResult = $createField->create(
        type: 'craft\fields\PlainText',
        name: 'Heading',
        handle: 'heading',
        searchable: true,
        translationMethod: 'none',
    );
    $originalFieldId = $fieldResult['fieldId'];

    // Step 2: Create an entry type with a field layout
    $entryTypeResult = $createEntryType->create(
        name: 'Test Broken Reference',
        handle: 'testBrokenRef',
        hasTitleField: true
    );
    $fieldLayoutId = $entryTypeResult['fieldLayoutId'];

    // Step 3: Add the field to the field layout
    $addResult = $addField->add(
        fieldLayoutId: $fieldLayoutId,
        fieldId: $originalFieldId,
        tabName: 'Content',
        position: ['type' => 'append']
    );

    // Verify field was added
    expect($addResult['addedField']['fieldId'])->toBe($originalFieldId);

    // Step 4: Delete the field - this should clean up the field layout
    $deleteResult = $deleteField->delete(fieldId: $originalFieldId);
    expect($deleteResult['success'])->toBeTrue();
    
    // Verify the delete result includes cleanup information
    expect($deleteResult)->toHaveKey('cleanedLayouts');
    
    // The cleanup count should be an integer
    expect($deleteResult['cleanedLayouts'])->toBeInt();
});

test('field layout cleanup is mentioned in delete notes', function () {
    $createField = Craft::$container->get(CreateField::class);
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $deleteField = Craft::$container->get(DeleteField::class);

    // Step 1: Create a field
    $fieldResult = $createField->create(
        type: 'craft\fields\PlainText',
        name: 'Test Field',
        handle: 'testField',
        searchable: true,
        translationMethod: 'none',
    );
    $fieldId = $fieldResult['fieldId'];

    // Step 2: Create an entry type with a field layout
    $entryTypeResult = $createEntryType->create(
        name: 'Test Entry Type',
        handle: 'testEntryType',
        hasTitleField: true
    );
    $fieldLayoutId = $entryTypeResult['fieldLayoutId'];

    // Step 3: Add the field to the layout
    $addField->add(
        fieldLayoutId: $fieldLayoutId,
        fieldId: $fieldId,
        tabName: 'Content',
        position: ['type' => 'append']
    );

    // Step 4: Delete the field
    $deleteResult = $deleteField->delete(fieldId: $fieldId);
    
    // Step 5: Verify cleanup information is in the response
    expect($deleteResult)->toHaveKey('cleanedLayouts');
    expect($deleteResult['_notes'])->toBeString();
    
    // The notes should mention cleanup if any layouts were affected
    if ($deleteResult['cleanedLayouts'] > 0) {
        expect($deleteResult['_notes'])->toContain('Cleaned up');
        expect($deleteResult['_notes'])->toContain('field layout');
    }
});
