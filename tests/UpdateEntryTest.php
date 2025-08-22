<?php

use markhuot\craftmcp\tools\UpdateEntry;
use markhuot\craftpest\factories\Entry;

it('can update entry title', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Original Title')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['title' => 'Updated Title'],
    );

    expect($response['title'])->toBe('Updated Title');
    
    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->title)->toBe('Updated Title');
});

it('can update custom fields', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Test Entry')
        ->body('Original body')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['body' => 'Updated body content'],
    );

    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->body)->toBe('Updated body content');
});

it('can update multiple fields at once', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Original Title')
        ->body('Original body')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: [
            'title' => 'New Title',
            'body' => 'New body content',
        ],
    );

    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->title)->toBe('New Title');
    expect($updatedEntry->body)->toBe('New body content');
});

it('can update entry slug', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Test Entry')
        ->slug('original-slug')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['slug' => 'new-custom-slug'],
    );

    expect($response['slug'])->toBe('new-custom-slug');
    
    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->slug)->toBe('new-custom-slug');
});

it('returns proper response format after update', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Response Test')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['title' => 'Updated Response Test'],
    );

    expect($response)->toHaveKeys(['entryId', 'title', 'slug', 'postDate', 'url']);
    expect($response['entryId'])->toBe($entry->id);
    expect($response['title'])->toBe('Updated Response Test');
    expect($response['url'])->toBeString();
});

it('preserves unchanged fields when updating', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Original Title')
        ->body('Original body')
        ->create();

    Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['title' => 'Updated Title Only'],
    );

    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->title)->toBe('Updated Title Only');
    expect($updatedEntry->body)->toBe('Original body');
});

it('can update with empty field data', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Test Entry')
        ->body('Some content')
        ->create();

    $response = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: [],
    );

    expect($response['entryId'])->toBe($entry->id);
    
    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->title)->toBe('Test Entry');
    expect($updatedEntry->body)->toBe('Some content');
});

it('can clear field content', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Test Entry')
        ->body('Content to clear')
        ->create();

    Craft::$container->get(UpdateEntry::class)->update(
        entryId: $entry->id,
        attributeAndFieldData: ['body' => ''],
    );

    $updatedEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($updatedEntry->body)->toBe(null);
});

it('updates entries from different sections', function () {
    $newsEntry = Entry::factory()
        ->section('news')
        ->title('News Entry')
        ->create();
    
    $pageEntry = Entry::factory()
        ->section('pages')
        ->title('Page Entry')
        ->create();

    $newsResponse = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $newsEntry->id,
        attributeAndFieldData: ['title' => 'Updated News'],
    );
    
    $pageResponse = Craft::$container->get(UpdateEntry::class)->update(
        entryId: $pageEntry->id,
        attributeAndFieldData: ['title' => 'Updated Page'],
    );

    expect($newsResponse['title'])->toBe('Updated News');
    expect($pageResponse['title'])->toBe('Updated Page');
});
