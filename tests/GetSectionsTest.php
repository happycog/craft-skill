<?php

use happycog\craftmcp\tools\GetSections;

it('gets all sections and entry types', function () {
    $response = Craft::$container->get(GetSections::class)->get();

    expect($response)->toBeArray();
    expect(count($response))->toBeGreaterThan(0);
    
    foreach ($response as $section) {
        expect($section)->toHaveKeys(['id', 'handle', 'name', 'type', 'entryTypes']);
        expect($section['id'])->toBeInt();
        expect($section['handle'])->toBeString();
        expect($section['name'])->toBeString();
        expect($section['type'])->toBeString();
        expect($section['entryTypes'])->toBeArray();
    }
});

it('includes entry type details for each section', function () {
    $response = Craft::$container->get(GetSections::class)->get();

    foreach ($response as $section) {
        expect($section['entryTypes'])->not->toBeEmpty();
        
        foreach ($section['entryTypes'] as $entryType) {
            expect($entryType)->toHaveKeys(['id', 'handle', 'name']);
            expect($entryType['id'])->toBeInt();
            expect($entryType['handle'])->toBeString();
            expect($entryType['name'])->toBeString();
        }
    }
});

it('returns sections with expected handles', function () {
    $response = Craft::$container->get(GetSections::class)->get();
    
    $handles = array_column($response, 'handle');
    
    expect($handles)->toContain('news');
    expect($handles)->toContain('pages');
});

it('returns proper section types', function () {
    $response = Craft::$container->get(GetSections::class)->get();
    
    $types = array_unique(array_column($response, 'type'));
    
    foreach ($types as $type) {
        expect($type)->toBeIn(['channel', 'single', 'structure']);
    }
});

it('has consistent data structure across all sections', function () {
    $response = Craft::$container->get(GetSections::class)->get();

    foreach ($response as $section) {
        expect($section['id'])->toBeGreaterThan(0);
        expect(strlen($section['handle']))->toBeGreaterThan(0);
        expect(strlen($section['name']))->toBeGreaterThan(0);
        expect(count($section['entryTypes']))->toBeGreaterThan(0);
        
        foreach ($section['entryTypes'] as $entryType) {
            expect($entryType['id'])->toBeGreaterThan(0);
            expect(strlen($entryType['handle']))->toBeGreaterThan(0);
            expect(strlen($entryType['name']))->toBeGreaterThan(0);
        }
    }
});

it('returns sections that can be used for creating entries', function () {
    $response = Craft::$container->get(GetSections::class)->get();
    
    $firstSection = $response[0];
    $sectionId = $firstSection['id'];
    $entryTypeId = $firstSection['entryTypes'][0]['id'];
    
    expect($sectionId)->toBeInt();
    expect($entryTypeId)->toBeInt();
    
    $section = Craft::$app->getEntries()->getSectionById($sectionId);
    expect($section)->not->toBeNull();
    
    $entryType = $section->getEntryTypes()[0];
    expect($entryType->id)->toBe($entryTypeId);
});