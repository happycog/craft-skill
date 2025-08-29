<?php

use happycog\craftmcp\tools\GetEntry;
use markhuot\craftpest\factories\Entry;

it('gets entry details', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('foo')
        ->body('bar')
        ->create();

    $response = Craft::$container->get(GetEntry::class)->get($entry->id);

    expect($response)->toMatchArray([
        'title' => 'foo',
        'body' => 'bar',
    ]);
});
