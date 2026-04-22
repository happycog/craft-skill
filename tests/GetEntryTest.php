<?php

use happycog\craftmcp\tools\GetEntry;
use markhuot\craftpest\factories\Entry;

it('gets entry details', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('foo')
        ->body('bar')
        ->create();

    $response = Craft::$container->get(GetEntry::class)->__invoke(entryId: $entry->id);

    expect($response)->toMatchArray([
        'title' => 'foo',
        'body' => 'bar',
    ]);
});

it('gets entry details by slug', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Entry By Slug')
        ->slug('entry-by-slug')
        ->body('bar')
        ->create();

    $response = Craft::$container->get(GetEntry::class)->__invoke(slug: $entry->slug);

    expect($response)->toMatchArray([
        'id' => $entry->id,
        'slug' => 'entry-by-slug',
        'title' => 'Entry By Slug',
    ]);
});

it('gets entry details by uri', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Entry By Uri')
        ->slug('entry-by-uri')
        ->create();

    expect($entry->uri)->toBeString();

    $response = Craft::$container->get(GetEntry::class)->__invoke(uri: $entry->uri);

    expect($response)->toMatchArray([
        'id' => $entry->id,
        'slug' => 'entry-by-uri',
        'title' => 'Entry By Uri',
    ]);
});

it('requires exactly one selector', function () {
    expect(fn() => Craft::$container->get(GetEntry::class)->__invoke())
        ->toThrow(\InvalidArgumentException::class, 'Provide exactly one of entryId, uri, or slug.');

    expect(fn() => Craft::$container->get(GetEntry::class)->__invoke(entryId: 1, slug: 'foo'))
        ->toThrow(\InvalidArgumentException::class, 'Provide exactly one of entryId, uri, or slug.');
});
