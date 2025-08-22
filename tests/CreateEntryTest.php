<?php

use markhuot\craftmcp\tools\CreateEntry;

beforeEach(function () {
    $this->createEntry = function (array $attributeAndFieldData) {
        $section = Craft::$app->getEntries()->getAllSections()[0];
        $sectionId = $section->id;
        $entryTypeId = $section->getEntryTypes()[0]->id;

        $response = Craft::$container->get(CreateEntry::class)->create(
            sectionId: $sectionId,
            entryTypeId: $entryTypeId,
            attributeAndFieldData: $attributeAndFieldData,
        );

        expect($response['entryId'])->not->toBeNull();

        $entry = \craft\elements\Entry::find()->id($response['entryId'])->one();

        expect($entry)->not->toBeNull();

        return $entry;
    };
});

it('can create entries and titles', function () {
    $entry = ($this->createEntry)([
        'title' => 'foo bar',
    ]);

    expect($entry->title)->toBe('foo bar');
});

it('can create custom text fields', function () {
    $entry = ($this->createEntry)([
        'title' => 'foo bar',
        'body' => 'baz qux',
    ]);

    expect($entry->body)->toBe('baz qux');
});
