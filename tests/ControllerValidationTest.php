<?php

test('controller rejects unknown parameters', function () {
    // Try to create entry with invalid parameters (fields and unknownParam)
    // Using obviously invalid IDs to avoid needing real section setup
    $response = $this->post('/api/entries', [
        'sectionId' => 999,
        'entryTypeId' => 999,
        'fields' => ['title' => 'Test'],  // Wrong - should be 'attributeAndFieldData'
        'unknownParam' => 'value'
    ]);

    // Should get validation error for invalid parameters
    $response->assertStatus(400);
    $data = json_decode($response->json()->json, true);

    expect($data)->toHaveKey('error');
    expect($data)->toHaveKey('errors');
    // Valinor's error message format
    expect($data['error'])->toContain('Unexpected key(s)');
    expect($data['error'])->toContain('fields');
    expect($data['error'])->toContain('unknownParam');
    // Verify it tells us the expected parameters
    expect($data['error'])->toContain('expected');
});

test('controller reports multiple validation errors at once', function () {
    // Create a test entry type first
    $createEntryType = Craft::$container->get(\happycog\craftmcp\tools\CreateEntryType::class);
    $entryTypeData = $createEntryType->create(
        name: 'Test Entry Type for Multiple Errors',
        handle: 'testEntryTypeForMultipleErrors' . time()
    );

    // Try to create a section with multiple errors:
    // 1. Invalid type (should be 'single', 'channel', or 'structure')
    // 2. Superfluous parameter (unknownParam)
    $response = $this->post('/api/sections', [
        'name' => 'Test Section',
        'type' => 'invalid',  // Invalid type
        'entryTypeIds' => [$entryTypeData['entryTypeId']],
        'handle' => 'testSection' . time(),
        'unknownParam' => 'value',  // Superfluous key
    ]);

    // Should get validation error
    $response->assertStatus(400);
    $data = json_decode($response->json()->json, true);

    // Verify we have both error fields
    expect($data)->toHaveKey('error');
    expect($data)->toHaveKey('errors');

    // Verify multiple errors are reported
    expect($data['errors'])->toBeArray();

    // Should have error about the invalid type parameter
    expect($data['errors'])->toHaveKey('type');
    expect($data['errors']['type'])->toBeArray();
    expect($data['errors']['type'][0])->toContain('Does not accept value of type string');
    expect($data['errors']['type'][0])->toContain("'single'|'channel'|'structure'");

    // Should have error about superfluous key
    expect($data['errors'])->toHaveKey('_superfluous');
    expect($data['errors']['_superfluous'][0])->toContain('Unexpected key(s)');
    expect($data['errors']['_superfluous'][0])->toContain('unknownParam');
});
