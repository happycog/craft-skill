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
    expect($data['error'])->toContain('Invalid parameters');
    expect($data['error'])->toContain('fields');
    expect($data['error'])->toContain('unknownParam');
    // Verify it tells us the valid parameters
    expect($data['error'])->toContain('Valid parameters are');
});
