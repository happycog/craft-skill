<?php

use happycog\craftmcp\tools\GetSites;

it('can get sites information', function () {
    $getSites = Craft::$container->get(GetSites::class);
    $sites = $getSites->get();

    expect($sites)->toBeArray();
    expect($sites)->not->toBeEmpty();

    $firstSite = $sites[0];
    expect($firstSite)->toHaveKeys(['id', 'name', 'handle', 'url', 'primary', 'language']);
    expect($firstSite['id'])->toBeInt();
    expect($firstSite['name'])->toBeString();
    expect($firstSite['handle'])->toBeString();
    expect($firstSite['primary'])->toBeBool();
    expect($firstSite['language'])->toBeString();
});

it('identifies primary site correctly', function () {
    $getSites = Craft::$container->get(GetSites::class);
    $sites = $getSites->get();

    $primarySites = array_filter($sites, fn($site) => $site['primary'] === true);
    expect($primarySites)->toHaveCount(1);
});

it('returns all enabled sites', function () {
    $getSites = Craft::$container->get(GetSites::class);
    $sites = $getSites->get();
    
    $craftSites = Craft::$app->getSites()->getAllSites();
    expect($sites)->toHaveCount(count($craftSites));
});