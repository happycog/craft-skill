<?php

use craft\elements\Entry;

test('PUT /api/sections/<id> updates a section', function () {
    // Create a test entry type first
    $createEntryType = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
    $entryTypeData = $createEntryType->create(
        name: 'Test Entry Type for Section',
        handle: 'testEntryTypeForSection' . time()
    );
    
    // Create a test section
    $createSection = Craft::$container->get(\happycog\craftmcp\tools\CreateSection::class);
    $sectionData = $createSection->create(
        name: 'Test Section',
        type: 'channel',
        entryTypeIds: [$entryTypeData['entryTypeId']],
        handle: 'testSection' . time()
    );
    
    $sectionId = $sectionData['sectionId'];
    
    // Update the section using the API route with path param (POST with _method=PUT)
    $response = $this->postJson('/api/sections/' . $sectionId, [
        '_method' => 'PUT',
        'name' => 'Updated Section Name',
    ]);
    
    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"name":"Updated Section Name"');
    expect($content)->toContain('"sectionId":' . $sectionId);
});

test('DELETE /api/sections/<id> deletes a section', function () {
    // Create a test entry type first
    $createEntryType = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
    $entryTypeData = $createEntryType->create(
        name: 'Test Entry Type for Delete Section',
        handle: 'testEntryTypeForDeleteSection' . time()
    );
    
    // Create a test section
    $createSection = Craft::$container->get(\happycog\craftmcp\tools\CreateSection::class);
    $sectionData = $createSection->create(
        name: 'Test Section to Delete',
        type: 'channel',
        entryTypeIds: [$entryTypeData['entryTypeId']],
        handle: 'testSectionDelete' . time()
    );
    
    $sectionId = $sectionData['sectionId'];
    
    // Delete the section using the API route with path param (POST with _method=DELETE)
    $response = $this->postJson('/api/sections/' . $sectionId, [
        '_method' => 'DELETE',
        'force' => false,
    ]);
    
    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"id":' . $sectionId);
    expect($content)->toContain('"name":"Test Section to Delete"');
});

test('PUT /api/entry-types/<id> updates an entry type', function () {
    // Create a test entry type first
    $createEntryType = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
    $entryTypeData = $createEntryType->create(
        name: 'Test Entry Type',
        handle: 'testEntryType' . time()
    );
    
    $entryTypeId = $entryTypeData['entryTypeId'];
    
    // Update the entry type using the API route with path param (POST with _method=PUT)
    $response = $this->postJson('/api/entry-types/' . $entryTypeId, [
        '_method' => 'PUT',
        'name' => 'Updated Entry Type Name',
    ]);
    
    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"name":"Updated Entry Type Name"');
    expect($content)->toContain('"id":' . $entryTypeId);
});

test('DELETE /api/entry-types/<id> deletes an entry type', function () {
    // Create a test entry type first
    $createEntryType = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
    $entryTypeData = $createEntryType->create(
        name: 'Test Entry Type to Delete',
        handle: 'testEntryTypeDelete' . time()
    );
    
    $entryTypeId = $entryTypeData['entryTypeId'];
    
    // Delete the entry type using the API route with path param (POST with _method=DELETE)
    $response = $this->postJson('/api/entry-types/' . $entryTypeId, [
        '_method' => 'DELETE',
        'force' => false,
    ]);
    
    $response->assertStatus(200);
    $content = $response->content;
    
    expect($content)->toContain('"deleted":true');
    expect($content)->toContain('"name":"Test Entry Type to Delete"');
});
