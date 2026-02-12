<?php

use craft\fields\Matrix;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\CreateEntryType;
use function happycog\craftmcp\helpers\getMatrixSubTypes;

test('getMatrixEntryTypes helper works with matrix fields', function () {
    // Create a couple of entry types to use as block types
    $createEntryType = Craft::$container->get(CreateEntryType::class);
    
    $blockType1 = $createEntryType->__invoke(
        name: 'Test Block 1',
        handle: 'testBlock1'
    );
    
    $blockType2 = $createEntryType->__invoke(
        name: 'Test Block 2',
        handle: 'testBlock2'
    );
    
    // Create a matrix field with these block types
    $createField = Craft::$container->get(CreateField::class);
    $result = $createField(
        'craft\fields\Matrix',
        'Test Matrix Field',
        [
            'handle' => 'testMatrixHelper',
            'settings' => [
                'entryTypes' => [$blockType1['id'], $blockType2['id']],
            ],
        ]
    );
    
    // Get the field from the database
    $field = Craft::$app->getFields()->getFieldById($result['id']);
    expect($field)->toBeInstanceOf(Matrix::class);
    
    // Use the helper function to get entry types
    $entryTypes = getMatrixSubTypes($field);
    
    // Verify the helper returns the expected entry types
    expect($entryTypes)->toHaveCount(2);
    expect($entryTypes[0])->toHaveProperty('id');
    expect($entryTypes[0])->toHaveProperty('handle');
    expect($entryTypes[1])->toHaveProperty('id');
    expect($entryTypes[1])->toHaveProperty('handle');
    
    // Verify handles match what we created
    $handles = array_map(fn($et) => $et->handle, $entryTypes);
    expect($handles)->toContain('testBlock1');
    expect($handles)->toContain('testBlock2');
});
