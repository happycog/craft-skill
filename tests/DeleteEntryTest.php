<?php

use markhuot\craftmcp\tools\DeleteEntry;
use markhuot\craftpest\factories\Entry;

it('can soft delete an entry (default behavior)', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Entry to Delete')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
    );

    expect($response['entryId'])->toBe($entry->id);
    expect($response['title'])->toBe('Entry to Delete');
    expect($response['deletedPermanently'])->toBeFalse();

    // Entry should be soft deleted (trashed, not permanently removed)
    $deletedEntry = \craft\elements\Entry::find()
        ->id($entry->id)
        ->trashed()
        ->one();
    
    expect($deletedEntry)->not->toBeNull();
    expect($deletedEntry->trashed)->toBeTrue();

    // Entry should not be found in normal queries
    $liveEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($liveEntry)->toBeNull();
});

it('can permanently delete an entry when specified', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Entry to Permanently Delete')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
        permanentlyDelete: true,
    );

    expect($response['entryId'])->toBe($entry->id);
    expect($response['title'])->toBe('Entry to Permanently Delete');
    expect($response['deletedPermanently'])->toBeTrue();

    // Entry should be completely removed from database
    $deletedEntry = \craft\elements\Entry::find()
        ->id($entry->id)
        ->trashed()
        ->one();
    
    expect($deletedEntry)->toBeNull();

    $liveEntry = \craft\elements\Entry::find()->id($entry->id)->one();
    expect($liveEntry)->toBeNull();
});

it('returns proper response format after deletion', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Response Test Entry')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
    );

    expect($response)->toHaveKeys([
        'entryId', 
        'title', 
        'slug', 
        'sectionId', 
        'sectionName', 
        'postDate', 
        'deletedPermanently'
    ]);
    expect($response['entryId'])->toBe($entry->id);
    expect($response['title'])->toBe('Response Test Entry');
    expect($response['sectionName'])->toBeString();
    expect($response['deletedPermanently'])->toBeBool();
});

it('throws exception when entry not found', function () {
    expect(function () {
        Craft::$container->get(DeleteEntry::class)->delete(
            entryId: 99999, // Non-existent ID
        );
    })->toThrow(\InvalidArgumentException::class, 'Entry with ID 99999 not found.');
});

it('can delete entries from different sections', function () {
    $newsEntry = Entry::factory()
        ->section('news')
        ->title('News Entry to Delete')
        ->create();
    
    $pageEntry = Entry::factory()
        ->section('pages')
        ->title('Page Entry to Delete')
        ->create();

    $newsResponse = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $newsEntry->id,
    );
    
    $pageResponse = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $pageEntry->id,
    );

    expect($newsResponse['sectionName'])->toBe('News');
    expect($pageResponse['sectionName'])->toBe('Pages');

    // Both should be soft deleted
    $deletedNews = \craft\elements\Entry::find()
        ->id($newsEntry->id)
        ->trashed()
        ->one();
    
    $deletedPage = \craft\elements\Entry::find()
        ->id($pageEntry->id)
        ->trashed()
        ->one();

    expect($deletedNews)->not->toBeNull();
    expect($deletedPage)->not->toBeNull();
});

it('includes section information in response', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Section Info Test')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
    );

    expect($response['sectionId'])->toBe($entry->sectionId);
    expect($response['sectionName'])->toBe('News');
});

it('handles entries with custom fields', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Custom Fields Entry')
        ->body('This entry has custom fields')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
    );

    expect($response['entryId'])->toBe($entry->id);
    expect($response['title'])->toBe('Custom Fields Entry');

    // Verify deletion worked
    $deletedEntry = \craft\elements\Entry::find()
        ->id($entry->id)
        ->trashed()
        ->one();
    
    expect($deletedEntry)->not->toBeNull();
});

it('can delete entries with various post date formats', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Custom Date Entry')
        ->postDate('2023-01-01 12:00:00')
        ->create();

    $response = Craft::$container->get(DeleteEntry::class)->delete(
        entryId: $entry->id,
    );

    expect($response['postDate'])->toBeString();
    expect($response['entryId'])->toBe($entry->id);
});