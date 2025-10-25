<?php

use craft\fieldlayoutelements\Heading;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\LineBreak;
use craft\fieldlayoutelements\Markdown;
use craft\fieldlayoutelements\Template;
use craft\fieldlayoutelements\Tip;
use happycog\craftmcp\tools\AddFieldToFieldLayout;
use happycog\craftmcp\tools\AddTabToFieldLayout;
use happycog\craftmcp\tools\AddUiElementToFieldLayout;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\MoveElementInFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;

beforeEach(function () {
    $entriesService = Craft::$app->getEntries();
    $fieldsService = Craft::$app->getFields();

    $fieldHandles = ['testFieldLayoutSimp1', 'testFieldLayoutSimp2', 'testFieldLayoutSimp3'];

    foreach ($fieldHandles as $handle) {
        $field = $fieldsService->getFieldByHandle($handle);
        if ($field) {
            $fieldsService->deleteField($field);
        }
    }

    for ($i = 1; $i <= 10; $i++) {
        $entryType = $entriesService->getEntryTypeByHandle("testFieldLayoutSimp{$i}");
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }

    $this->createdEntryTypeIds = [];
    $this->createdFieldIds = [];

    $this->createField = function (string $name, array $options = []) {
        static $fieldCounter = 0;
        $fieldCounter++;
        
        $createField = Craft::$container->get(CreateField::class);

        $result = $createField->create(
            name: $name,
            handle: $options['handle'] ?? "testFieldLayoutSimp{$fieldCounter}",
            type: $options['type'] ?? 'craft\\fields\\PlainText',
            instructions: $options['instructions'] ?? '',
            searchable: $options['searchable'] ?? true,
            translationMethod: $options['translationMethod'] ?? 'none',
            settings: $options['settings'] ?? []
        );

        $this->createdFieldIds[] = $result['fieldId'];
        return $result;
    };

    $this->createFieldLayout = function (array $tabs = []) {
        static $counter = 0;
        $counter++;
        
        $createEntryType = Craft::$container->get(CreateEntryType::class);
        $getFieldLayout = Craft::$container->get(GetFieldLayout::class);

        $entryTypeResult = $createEntryType->create(
            name: "Test Field Layout {$counter}",
            handle: "testFieldLayoutSimp{$counter}",
            hasTitleField: true
        );

        $entryTypeId = $entryTypeResult['entryTypeId'];
        $fieldLayoutId = $entryTypeResult['fieldLayoutId'];
        
        $this->createdEntryTypeIds[] = $entryTypeId;

        throw_unless($fieldLayoutId, \RuntimeException::class, "Entry type {$entryTypeId} does not have a field layout");

        $layoutData = $getFieldLayout->get($fieldLayoutId);
        $layoutData['fieldLayoutId'] = $fieldLayoutId;
        
        return $layoutData;
    };
});

afterEach(function () {
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

test('add tab with prepend positioning', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    
    $layoutResult = ($this->createFieldLayout)();

    $result = $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'First Tab',
        position: ['type' => 'prepend']
    );

    expect($result)->toHaveKey('fieldLayout');
    expect($result['fieldLayout']['tabs'])->toHaveCount(2);
    expect($result['fieldLayout']['tabs'][0]['name'])->toBe('First Tab');
    expect($result['fieldLayout']['tabs'][1]['name'])->toBe('Content');
});

test('add tab with append positioning', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)();

    $result = $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Last Tab',
        position: ['type' => 'append']
    );

    expect($result['fieldLayout']['tabs'])->toHaveCount(2);
    expect($result['fieldLayout']['tabs'][0]['name'])->toBe('Content');
    expect($result['fieldLayout']['tabs'][1]['name'])->toBe('Last Tab');
});

test('add tab before/after existing tab', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)();

    $tabResult = $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Tab A',
        position: ['type' => 'append']
    );
    
    $tabResult = $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Tab C',
        position: ['type' => 'append']
    );

    $result = $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Tab B',
        position: ['type' => 'after', 'tabName' => 'Tab A']
    );

    expect($result['fieldLayout']['tabs'])->toHaveCount(4);
    expect($result['fieldLayout']['tabs'][0]['name'])->toBe('Content');
    expect($result['fieldLayout']['tabs'][1]['name'])->toBe('Tab A');
    expect($result['fieldLayout']['tabs'][2]['name'])->toBe('Tab B');
    expect($result['fieldLayout']['tabs'][3]['name'])->toBe('Tab C');
});

test('error when positioning tab relative to non-existent tab', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)();

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'New Tab',
        position: ['type' => 'after', 'tabName' => 'NonExistent']
    );
})->throws(RuntimeException::class, "Tab with name 'NonExistent' not found");

test('add field with prepend/append to existing tab', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);

    $field = ($this->createField)('Test Field');
    $layoutResult = ($this->createFieldLayout)();

    $result = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    expect($result)->toHaveKey('fieldLayout');
    expect($result)->toHaveKey('addedField');
    expect($result['addedField']['fieldId'])->toBe($field['fieldId']);
    
    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab)->not->toBeNull();
    expect($contentTab['elements'])->toHaveCount(2);
    expect($contentTab['elements'][0]['fieldId'])->toBe($field['fieldId']);
});

test('add field with before/after positioning relative to element UID', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);

    $field1 = ($this->createField)('Field 1');
    $field2 = ($this->createField)('Field 2');
    
    $layoutResult = ($this->createFieldLayout)();

    $result1 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field1['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $elementUid = $result1['addedField']['uid'];

    $result2 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field2['fieldId'],
        tabName: 'Content',
        position: ['type' => 'before', 'elementUid' => $elementUid]
    );

    $contentTab = collect($result2['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(3);
    expect($contentTab['elements'][0]['fieldId'])->toBe($field2['fieldId']);
    expect($contentTab['elements'][1]['fieldId'])->toBe($field1['fieldId']);
});

test('add field with custom configuration', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);

    $field = ($this->createField)('Test Field');
    $layoutResult = ($this->createFieldLayout)();

    $result = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend'],
        required: true,
        width: 50
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    $addedField = $contentTab['elements'][0];
    
    expect($addedField['required'])->toBeTrue();
    expect($addedField['width'])->toBe(50);
});

test('error when adding field to non-existent tab', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);

    $field = ($this->createField)('Test Field', ['handle' => 'testFieldLayoutSimp1']);
    $layoutResult = ($this->createFieldLayout)([]);

    $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'NonExistent',
        position: ['type' => 'prepend']
    );
})->throws(RuntimeException::class, "Tab with name 'NonExistent' not found");

test('error when positioning field relative to non-existent element UID', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);

    $field = ($this->createField)('Test Field', ['handle' => 'testFieldLayoutSimp1']);
    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Content',
        position: ['type' => 'prepend']
    );

    $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Content',
        position: ['type' => 'before', 'elementUid' => 'non-existent-uid']
    );
})->throws(RuntimeException::class, "Element with UID 'non-existent-uid' not found");

test('add heading UI element', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Content',
        position: ['type' => 'prepend']
    );

    $result = $addUiElement->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        tabName: 'Content',
        elementType: Heading::class,
        config: ['heading' => 'Section Title'],
        position: ['type' => 'prepend']
    );

    expect($result)->toHaveKey('addedElement');
    expect($result['addedElement']['type'])->toBe(Heading::class);
    
    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(1);
});

test('add tip UI element with all config', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Content',
        position: ['type' => 'prepend']
    );

    $result = $addUiElement->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        tabName: 'Content',
        elementType: Tip::class,
        config: [
            'tip' => 'This is a warning message',
            'style' => 'warning',
            'dismissible' => true
        ],
        position: ['type' => 'prepend']
    );

    expect($result['addedElement']['type'])->toBe(Tip::class);
});

test('add all UI element types', function () {
    $addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)();

    $uiElements = [
        [Heading::class, ['heading' => 'Test Heading']],
        [Tip::class, ['tip' => 'Test Tip', 'type' => 'info', 'style' => 'warning']],
        [Template::class, ['template' => '{{ entry.title }}']],
        [HorizontalRule::class, []],
        [LineBreak::class, []],
    ];

    foreach ($uiElements as [$type, $config]) {
        $result = $addUiElement->add(
            fieldLayoutId: $layoutResult['fieldLayoutId'],
            tabName: 'Content',
            elementType: $type,
            config: $config,
            position: ['type' => 'append']
        );
        
        expect($result['addedElement']['type'])->toBe($type);
    }

    // Use the last result which has the complete in-memory state
    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(6);
});

test('error when UI element missing required config', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Content',
        position: ['type' => 'prepend']
    );

    $addUiElement->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        tabName: 'Content',
        elementType: Heading::class,
        config: [],
        position: ['type' => 'prepend']
    );
})->throws(RuntimeException::class, "Heading requires 'heading' text in config");

test('remove field by UID', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);

    $field = ($this->createField)('Test Field');
    $layoutResult = ($this->createFieldLayout)();

    $addResult = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $elementUid = $addResult['addedField']['uid'];

    $result = $removeElement->remove(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $elementUid
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(1);
});

test('remove UI element by UID', function () {
    $addUiElement = Craft::$container->get(AddUiElementToFieldLayout::class);
    $removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)();

    $addResult = $addUiElement->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        tabName: 'Content',
        elementType: Heading::class,
        config: ['heading' => 'Test'],
        position: ['type' => 'prepend']
    );

    $elementUid = $addResult['addedElement']['uid'];

    $result = $removeElement->remove(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $elementUid
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(1);
});

test('error when removing with non-existent UID', function () {
    $removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $removeElement->remove(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: 'non-existent-uid'
    );
})->throws(RuntimeException::class, "Element with UID 'non-existent-uid' not found");

test('other elements preserved after removal', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $removeElement = Craft::$container->get(RemoveElementFromFieldLayout::class);

    $field1 = ($this->createField)('Field 1');
    $field2 = ($this->createField)('Field 2');
    
    $layoutResult = ($this->createFieldLayout)();

    $addResult1 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field1['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field2['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append']
    );

    $result = $removeElement->remove(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $addResult1['addedField']['uid']
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(2);
    expect($contentTab['elements'][1]['fieldId'])->toBe($field2['fieldId']);
});

test('move element within same tab', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $moveElement = Craft::$container->get(MoveElementInFieldLayout::class);

    $field1 = ($this->createField)('Field 1');
    $field2 = ($this->createField)('Field 2');
    
    $layoutResult = ($this->createFieldLayout)();

    $result1 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field1['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $result2 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field2['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append']
    );

    $result = $moveElement->move(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $result2['addedField']['uid'],
        tabName: 'Content',
        position: ['type' => 'before', 'elementUid' => $result1['addedField']['uid']]
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    expect($contentTab['elements'])->toHaveCount(3);
    expect($contentTab['elements'][0]['fieldId'])->toBe($field2['fieldId']);
    expect($contentTab['elements'][1]['fieldId'])->toBe($field1['fieldId']);
});

test('move element to different tab', function () {
    $addTab = Craft::$container->get(AddTabToFieldLayout::class);
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $moveElement = Craft::$container->get(MoveElementInFieldLayout::class);

    $field = ($this->createField)('Test Field');
    
    $layoutResult = ($this->createFieldLayout)();

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Tab A',
        position: ['type' => 'append']
    );

    $addTab->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        name: 'Tab B',
        position: ['type' => 'append']
    );

    $addResult = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Tab A',
        position: ['type' => 'prepend']
    );

    $result = $moveElement->move(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $addResult['addedField']['uid'],
        tabName: 'Tab B',
        position: ['type' => 'prepend']
    );

    $tabA = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Tab A');
    $tabB = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Tab B');
    
    expect($tabA['elements'])->toHaveCount(0);
    expect($tabB['elements'])->toHaveCount(1);
    expect($tabB['elements'][0]['fieldId'])->toBe($field['fieldId']);
});

test('move element with before/after positioning', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $moveElement = Craft::$container->get(MoveElementInFieldLayout::class);

    $field1 = ($this->createField)('Field 1');
    $field2 = ($this->createField)('Field 2');
    $field3 = ($this->createField)('Field 3');
    
    $layoutResult = ($this->createFieldLayout)();

    $result1 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field1['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $result2 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field2['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append']
    );

    $result3 = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field3['fieldId'],
        tabName: 'Content',
        position: ['type' => 'append']
    );

    $result = $moveElement->move(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $result3['addedField']['uid'],
        tabName: 'Content',
        position: ['type' => 'after', 'elementUid' => $result1['addedField']['uid']]
    );

    $contentTab = collect($result['fieldLayout']['tabs'])->firstWhere('name', 'Content');
    
    // After moving field3 after field1, order should be:
    // [0] = title field (native)
    // [1] = field1
    // [2] = field3 (moved here, after field1)
    // [3] = field2
    
    expect($contentTab['elements'])->toHaveCount(4);
    
    // Find elements by fieldId to avoid index assumptions
    $elementsByFieldId = collect($contentTab['elements'])
        ->filter(fn($el) => isset($el['fieldId']))
        ->keyBy('fieldId');
    
    expect($elementsByFieldId)->toHaveKey($field1['fieldId']);
    expect($elementsByFieldId)->toHaveKey($field2['fieldId']);
    expect($elementsByFieldId)->toHaveKey($field3['fieldId']);
    
    // Check order by finding indices
    $field1Index = array_search($field1['fieldId'], array_column($contentTab['elements'], 'fieldId'));
    $field2Index = array_search($field2['fieldId'], array_column($contentTab['elements'], 'fieldId'));
    $field3Index = array_search($field3['fieldId'], array_column($contentTab['elements'], 'fieldId'));
    
    expect($field1Index)->toBeLessThan($field3Index);
    expect($field3Index)->toBeLessThan($field2Index);
});

test('error when moving to non-existent tab', function () {
    $addField = Craft::$container->get(AddFieldToFieldLayout::class);
    $moveElement = Craft::$container->get(MoveElementInFieldLayout::class);

    $field = ($this->createField)('Test Field');
    $layoutResult = ($this->createFieldLayout)();

    $addResult = $addField->add(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        fieldId: $field['fieldId'],
        tabName: 'Content',
        position: ['type' => 'prepend']
    );

    $moveElement->move(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: $addResult['addedField']['uid'],
        tabName: 'NonExistent',
        position: ['type' => 'prepend']
    );
})->throws(RuntimeException::class, "Tab with name 'NonExistent' not found");

test('error when moving non-existent element UID', function () {
    $moveElement = Craft::$container->get(MoveElementInFieldLayout::class);

    $layoutResult = ($this->createFieldLayout)([
        ['name' => 'Content', 'elements' => []]
    ]);

    $moveElement->move(
        fieldLayoutId: $layoutResult['fieldLayoutId'],
        elementUid: 'non-existent-uid',
        tabName: 'Content',
        position: ['type' => 'prepend']
    );
})->throws(RuntimeException::class, "Element with UID 'non-existent-uid' not found");
