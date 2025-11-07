<?php

use happycog\craftmcp\tools\GetHealth;

test('health endpoint returns ok status', function () {
    $response = $this->get('/api/health');

    $response->assertStatus(200);
    $data = json_decode($response->json()->json, true);
    
    expect($data)->toHaveKey('status')
        ->and($data['status'])->toBe('ok')
        ->and($data)->toHaveKey('plugin')
        ->and($data)->toHaveKey('craft')
        ->and($data)->toHaveKey('site');
});

test('health response includes plugin information', function () {
    $response = $this->get('/api/health');

    $data = json_decode($response->json()->json, true);
    
    expect($data['plugin'])->toHaveKey('name')
        ->and($data['plugin'])->toHaveKey('version')
        ->and($data['plugin'])->toHaveKey('installed')
        ->and($data['plugin']['installed'])->toBeTrue()
        ->and($data['plugin']['name'])->toBe('Craft Skill');
});

test('health response includes craft information', function () {
    $response = $this->get('/api/health');

    $data = json_decode($response->json()->json, true);
    
    expect($data['craft'])->toHaveKey('version')
        ->and($data['craft'])->toHaveKey('edition')
        ->and($data['craft']['version'])->toBeString()
        ->and($data['craft']['edition'])->toBeString();
});

test('health response includes site information', function () {
    $response = $this->get('/api/health');

    $data = json_decode($response->json()->json, true);
    
    expect($data['site'])->toHaveKey('name')
        ->and($data['site'])->toHaveKey('baseUrl')
        ->and($data['site']['name'])->toBeString()
        ->and($data['site']['baseUrl'])->toBeString();
});

test('GetHealth tool returns expected structure', function () {
    $tool = Craft::$container->get(GetHealth::class);
    $result = $tool->get();

    expect($result)->toHaveKey('status')
        ->and($result['status'])->toBe('ok')
        ->and($result)->toHaveKey('plugin')
        ->and($result)->toHaveKey('craft')
        ->and($result)->toHaveKey('site')
        ->and($result['plugin'])->toHaveKey('name')
        ->and($result['plugin'])->toHaveKey('version')
        ->and($result['plugin'])->toHaveKey('installed')
        ->and($result['craft'])->toHaveKey('version')
        ->and($result['craft'])->toHaveKey('edition')
        ->and($result['site'])->toHaveKey('name')
        ->and($result['site'])->toHaveKey('baseUrl');
});
