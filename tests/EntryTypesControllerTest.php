<?php

use craft\models\EntryType;

test('POST /api/entry-types creates an entry type', function () {
    $handle = 'testArticle' . time();
    $response = $this->postJson('/api/entry-types', [
        'name' => 'Test Article',
        'handle' => $handle,
        'hasTitleField' => true,
        'icon' => 'newspaper',
        'color' => 'blue',
    ]);

    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"name":"Test Article"');
    expect($content)->toContain('"handle":"' . $handle . '"');
    expect($content)->toContain('"icon":"newspaper"');
    expect($content)->toContain('"color":"blue"');
    expect($content)->toContain('"entryTypeId"');
});

test('GET /api/entry-types lists entry types', function () {
    // Create a test entry type
    $entriesService = Craft::$app->getEntries();
    $entryType = new EntryType();
    $entryType->name = 'Test Entry Type';
    $entryType->handle = 'testEntryType' . time();
    $entriesService->saveEntryType($entryType);

    $response = $this->get('/api/entry-types');

    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"id":' . $entryType->id);
    expect($content)->toContain('"name":"Test Entry Type"');
});

test('GET /api/entry-types with entryTypeIds filter', function () {
    // Create two test entry types
    $entriesService = Craft::$app->getEntries();
    
    $entryType1 = new EntryType();
    $entryType1->name = 'Test Entry Type 1';
    $entryType1->handle = 'testEntryType1' . time();
    $entriesService->saveEntryType($entryType1);
    
    $entryType2 = new EntryType();
    $entryType2->name = 'Test Entry Type 2';
    $entryType2->handle = 'testEntryType2' . time();
    $entriesService->saveEntryType($entryType2);

    $response = $this->get('/api/entry-types?entryTypeIds[]=' . $entryType1->id);

    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"id":' . $entryType1->id);
    expect($content)->not->toContain('"id":' . $entryType2->id);
});
