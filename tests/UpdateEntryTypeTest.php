<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\UpdateEntryType;

beforeEach(function () {
    // Clean up any existing test entry types
    $entriesService = Craft::$app->getEntries();
    $testHandles = [
        'testUpdateEntryType', 'originalHandle', 'updatedHandle', 'duplicateHandle'
    ];

    foreach ($testHandles as $handle) {
        $entryType = $entriesService->getEntryTypeByHandle($handle);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }

    // Track created entry types for cleanup
    $this->createdEntryTypeIds = [];

    $this->createEntryType = function (string $name, array $options = []) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->create(
            name: $name,
            handle: $options['handle'] ?? null,
            hasTitleField: $options['hasTitleField'] ?? true,
            titleTranslationMethod: $options['titleTranslationMethod'] ?? 'site',
            titleTranslationKeyFormat: $options['titleTranslationKeyFormat'] ?? null,
            titleFormat: $options['titleFormat'] ?? null,
            icon: $options['icon'] ?? null,
            color: $options['color'] ?? null
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];
        return $result;
    };

    $this->updateEntryType = function (int $entryTypeId, array $updates = []) {
        $updateEntryType = Craft::$container->get(UpdateEntryType::class);

        return $updateEntryType->update(
            entryTypeId: $entryTypeId,
            name: $updates['name'] ?? null,
            handle: $updates['handle'] ?? null,
            titleTranslationMethod: $updates['titleTranslationMethod'] ?? null,
            titleTranslationKeyFormat: $updates['titleTranslationKeyFormat'] ?? null,
            titleFormat: $updates['titleFormat'] ?? null,
            icon: $updates['icon'] ?? null,
            color: $updates['color'] ?? null,
            description: $updates['description'] ?? null,
            showSlugField: $updates['showSlugField'] ?? null,
            showStatusField: $updates['showStatusField'] ?? null
        );
    };
});

afterEach(function () {
    // Clean up any entry types that weren't deleted during the test
    $entriesService = Craft::$app->getEntries();

    foreach ($this->createdEntryTypeIds ?? [] as $entryTypeId) {
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
});

it('can update entry type name', function () {
    $created = ($this->createEntryType)('Original Name', ['handle' => 'originalHandle']);

    $result = ($this->updateEntryType)($created['entryTypeId'], ['name' => 'Updated Name']);

    expect($result['name'])->toBe('Updated Name');
    expect($result['handle'])->toBe('originalHandle'); // Should remain unchanged

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->name)->toBe('Updated Name');
});

it('can update entry type handle', function () {
    $created = ($this->createEntryType)('Test Entry Type', ['handle' => 'originalHandle']);

    $result = ($this->updateEntryType)($created['entryTypeId'], ['handle' => 'updatedHandle']);

    expect($result['handle'])->toBe('updatedHandle');

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->handle)->toBe('updatedHandle');
});

it('can update translation method', function () {
    $created = ($this->createEntryType)('Test Entry Type', ['titleTranslationMethod' => 'site']);

    $result = ($this->updateEntryType)($created['entryTypeId'], ['titleTranslationMethod' => 'language']);

    expect($result['titleTranslationMethod'])->toBe(\craft\base\Field::TRANSLATION_METHOD_LANGUAGE);

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->titleTranslationMethod)->toBe(\craft\base\Field::TRANSLATION_METHOD_LANGUAGE);
});

it('can update icon and color', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], [
        'icon' => 'news',
        'color' => 'blue'
    ]);

    // Verify in database (formatter doesn't include icon/color)
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->icon)->toBe('news');
    expect($entryType->color?->value)->toBe('blue');
});

it('can update translation key format', function () {
    $created = ($this->createEntryType)('Test Entry Type', ['titleTranslationMethod' => 'custom']);

    $keyFormat = '{site}_{slug}';
    $result = ($this->updateEntryType)($created['entryTypeId'], [
        'titleTranslationKeyFormat' => $keyFormat
    ]);

    expect($result['titleTranslationKeyFormat'])->toBe($keyFormat);

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->titleTranslationKeyFormat)->toBe($keyFormat);
});

it('can update title format', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $titleFormat = '{name} - {dateCreated|date}';
    $result = ($this->updateEntryType)($created['entryTypeId'], [
        'titleFormat' => $titleFormat
    ]);

    expect($result['titleFormat'])->toBe($titleFormat);

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->titleFormat)->toBe($titleFormat);
});

it('can update multiple properties at once', function () {
    $created = ($this->createEntryType)('Original Name');

    $result = ($this->updateEntryType)($created['entryTypeId'], [
        'name' => 'Updated Name',
        'icon' => 'article',
        'color' => 'red'
    ]);

    expect($result['name'])->toBe('Updated Name');
    expect($result['icon'])->toBe('article');
    expect($result['color'])->toBe('red');
});

it('returns expected data when no updates are made', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], []);

    // Should still return the formatted entry type data
    expect($result['name'])->toBe('Test Entry Type');
    expect($result['id'])->toBe($created['entryTypeId']);
});

it('throws exception for non-existent entry type', function () {
    expect(fn() => ($this->updateEntryType)(99999, ['name' => 'Test']))
        ->toThrow(\RuntimeException::class, 'Entry type with ID 99999 not found');
});

it('throws exception for duplicate handle', function () {
    $created1 = ($this->createEntryType)('First Entry Type', ['handle' => 'duplicateHandle']);
    $created2 = ($this->createEntryType)('Second Entry Type', ['handle' => 'secondHandle']);

    expect(fn() => ($this->updateEntryType)($created2['entryTypeId'], ['handle' => 'duplicateHandle']))
        ->toThrow(\Exception::class, 'Failed to save entry type: handle: Handle "duplicateHandle" has already been taken.');
});

it('throws exception for invalid translation method', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    expect(fn() => ($this->updateEntryType)($created['entryTypeId'], ['titleTranslationMethod' => 'invalid']))
        ->toThrow(\InvalidArgumentException::class, "Invalid translation method 'invalid'");
});

it('handles invalid color gracefully', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], ['color' => 'rainbow']);

    // Invalid color should result in null
    expect($result['color'])->toBeNull();
});

it('includes control panel edit URL', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], ['name' => 'Updated Name']);

    expect($result['editUrl'])->toContain('/settings/entry-types/');
    expect($result['editUrl'])->toContain((string)$created['entryTypeId']);
});

it('preserves field layout ID', function () {
    $created = ($this->createEntryType)('Test Entry Type');
    $originalFieldLayoutId = $created['fieldLayoutId'];

    $result = ($this->updateEntryType)($created['entryTypeId'], ['name' => 'Updated Name']);

    expect($result['fieldLayoutId'])->toBe($originalFieldLayoutId);
});

it('returns all expected response fields', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], ['name' => 'Updated Name']);

    expect($result)->toHaveKeys([
        'id',
        'name',
        'handle',
        'description',
        'hasTitleField',
        'titleTranslationMethod',
        'titleTranslationKeyFormat',
        'titleFormat',
        'icon',
        'color',
        'showSlugField',
        'showStatusField',
        'fieldLayoutId',
        'uid',
        'fields',
        'editUrl'
    ]);
});

it('can update showSlugField', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], ['showSlugField' => false]);

    expect($result['showSlugField'])->toBeFalse();

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->showSlugField)->toBeFalse();
});

it('can update showStatusField', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], ['showStatusField' => false]);

    expect($result['showStatusField'])->toBeFalse();

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->showStatusField)->toBeFalse();
});

it('can update both showSlugField and showStatusField', function () {
    $created = ($this->createEntryType)('Test Entry Type');

    $result = ($this->updateEntryType)($created['entryTypeId'], [
        'showSlugField' => false,
        'showStatusField' => false
    ]);

    expect($result['showSlugField'])->toBeFalse();
    expect($result['showStatusField'])->toBeFalse();

    // Verify in database
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType->showSlugField)->toBeFalse();
    expect($entryType->showStatusField)->toBeFalse();
});
