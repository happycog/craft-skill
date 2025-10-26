<?php

test('POST /api/sites creates a site', function () {
    // Create a site using the API route
    $response = $this->postJson('/api/sites', [
        'name' => 'Test API Site',
        'baseUrl' => 'https://test-api.com',
        'language' => 'en-US',
        'handle' => 'testApiSite' . time()
    ]);

    $response->assertStatus(200);
    $content = $response->content;

    expect($content)->toContain('"name":"Test API Site"')
        ->and($content)->toContain('"language":"en-US"')
        ->and($content)->toContain('"baseUrl":"https://test-api.com"')
        ->and($content)->toContain('"siteId"');

    // Clean up
    $data = json_decode($content, true);
    $sitesService = Craft::$app->getSites();
    $site = $sitesService->getSiteById($data['siteId']);
    if ($site) {
        $sitesService->deleteSite($site);
    }
});

test('PUT /api/sites/<id> updates a site', function () {
    // Create a test site first
    $createSite = Craft::$container->get(\happycog\craftmcp\tools\CreateSite::class);
    $siteData = $createSite->create(
        name: 'Test Site for Update',
        baseUrl: 'https://test.com',
        language: 'en-US',
        handle: 'testSiteUpdate' . time()
    );

    $siteId = $siteData['siteId'];

    // Update the site using the API route with path param (POST with _method=PUT)
    $response = $this->postJson('/api/sites/' . $siteId, [
        '_method' => 'PUT',
        'name' => 'Updated Site Name',
    ]);

    $response->assertStatus(200);
    $content = $response->content;

    expect($content)->toContain('"name":"Updated Site Name"')
        ->and($content)->toContain('"siteId":' . $siteId);

    // Clean up
    $sitesService = Craft::$app->getSites();
    $site = $sitesService->getSiteById($siteId);
    if ($site) {
        $sitesService->deleteSite($site);
    }
});

test('DELETE /api/sites/<id> deletes a site', function () {
    // Create a test site first
    $createSite = Craft::$container->get(\happycog\craftmcp\tools\CreateSite::class);
    $siteData = $createSite->create(
        name: 'Test Site to Delete',
        baseUrl: 'https://delete-test.com',
        language: 'en-US',
        handle: 'testSiteDelete' . time()
    );

    $siteId = $siteData['siteId'];

    // Delete the site using the API route with path param (POST with _method=DELETE)
    $response = $this->postJson('/api/sites/' . $siteId, [
        '_method' => 'DELETE',
        'force' => false,
    ]);

    $response->assertStatus(200);
    $content = $response->content;

    expect($content)->toContain('"id":' . $siteId)
        ->and($content)->toContain('"name":"Test Site to Delete"');
});

test('GET /api/sites lists all sites', function () {
    // Get all sites using the API route
    $response = $this->getJson('/api/sites');

    $response->assertStatus(200);
    $content = $response->content;

    // Should return an array
    expect($content)->toContain('[')
        ->and($content)->toContain('"id"')
        ->and($content)->toContain('"name"')
        ->and($content)->toContain('"handle"');
});
