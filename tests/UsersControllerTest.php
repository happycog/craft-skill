<?php

test('GET /api/users lists users', function () {
    $user = createTestUser();

    $response = $this->get('/api/users');

    $response->assertStatus(200);
    $content = $response->content;

    expect($content)->toContain('"results"')
        ->and($content)->toContain('"id":' . $user->id)
        ->and($content)->toContain('"email":"' . $user->email . '"');
});

test('GET /api/users/<id> gets a user', function () {
    $user = createTestUser();

    $response = $this->get('/api/users/' . $user->id);

    $response->assertStatus(200);
    $content = $response->content;

    expect($content)->toContain('"id":' . $user->id)
        ->and($content)->toContain('"email":"' . $user->email . '"');
});
