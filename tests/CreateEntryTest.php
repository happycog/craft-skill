<?php

use happycog\craftmcp\tools\CreateEntry;

beforeEach(function () {
    $this->createEntry = function (array $attributeAndFieldData, ?int $siteId = null) {
        $section = Craft::$app->getEntries()->getSectionByHandle('news');
        $sectionId = $section->id;
        $entryTypeId = $section->getEntryTypes()[0]->id;

        $response = Craft::$container->get(CreateEntry::class)->create(
            sectionId: $sectionId,
            entryTypeId: $entryTypeId,
            siteId: $siteId,
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

it('creates entry for primary site when siteId is not provided', function () {
    $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

    $entry = ($this->createEntry)([
        'title' => 'Primary site entry',
    ]);

    expect($entry->siteId)->toBe($primarySiteId);
});

it('creates entry for specified site when siteId is provided', function () {
    $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

    $entry = ($this->createEntry)([
        'title' => 'Specific site entry',
    ], $primarySiteId);

    expect($entry->siteId)->toBe($primarySiteId);
});

it('throws exception for invalid siteId', function () {
    $section = Craft::$app->getEntries()->getAllSections()[0];
    $sectionId = $section->id;
    $entryTypeId = $section->getEntryTypes()[0]->id;

    $createEntry = Craft::$container->get(CreateEntry::class);

    expect(fn() => $createEntry->create(
        sectionId: $sectionId,
        entryTypeId: $entryTypeId,
        siteId: 99999, // Invalid site ID
        attributeAndFieldData: ['title' => 'Test'],
    ))->toThrow(InvalidArgumentException::class, 'Site with ID 99999 does not exist.');
});
