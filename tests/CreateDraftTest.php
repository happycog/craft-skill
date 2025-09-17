<?php

use happycog\craftmcp\tools\CreateDraft;
use happycog\craftmcp\tools\CreateEntry;

beforeEach(function () {
    $this->section = Craft::$app->getEntries()->getAllSections()[0];
    $this->sectionId = $this->section->id;
    $this->entryTypeId = $this->section->getEntryTypes()[0]->id;
    
    // Helper to create a published entry for draft testing
    $this->createPublishedEntry = function (array $attributeAndFieldData = []) {
        $createEntry = Craft::$container->get(CreateEntry::class);
        $response = $createEntry->create(
            sectionId: $this->sectionId,
            entryTypeId: $this->entryTypeId,
            attributeAndFieldData: array_merge(['title' => 'Test Entry'], $attributeAndFieldData)
        );
        
        return $response['entryId'];
    };
});

it('can create draft from scratch', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    $response = $createDraft->create(
        sectionId: $this->sectionId,
        entryTypeId: $this->entryTypeId,
        attributeAndFieldData: ['title' => 'Draft Title']
    );

    expect($response)->toHaveKeys(['draftId', 'canonicalId', 'title', 'slug', 'draftName', 'provisional', 'url']);
    expect($response['title'])->toBe('Draft Title');
    expect($response['canonicalId'])->not->toBeNull(); // From scratch creates canonical then draft
    expect($response['provisional'])->toBeFalse();
    expect($response['_notes'])->toBe('The draft was successfully created.');
});

it('can create draft from existing entry', function () {
    $canonicalId = ($this->createPublishedEntry)(['title' => 'Original Entry']);
    
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    $response = $createDraft->create(
        canonicalId: $canonicalId,
        draftName: 'Test Draft',
        draftNotes: 'Test notes'
    );

    expect($response)->toHaveKeys(['draftId', 'canonicalId', 'title', 'draftName', 'draftNotes']);
    expect($response['canonicalId'])->toBe($canonicalId);
    expect($response['title'])->toBe('Original Entry');
    expect($response['draftName'])->toBe('Test Draft');
    expect($response['draftNotes'])->toBe('Test notes');
    
    $draft = \craft\elements\Entry::find()->id($response['draftId'])->drafts()->one();
    expect($draft)->not->toBeNull();
    expect($draft->getIsDraft())->toBeTrue();
    expect($draft->canonicalId)->toBe($canonicalId);
});

it('can create provisional draft', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    $response = $createDraft->create(
        sectionId: $this->sectionId,
        entryTypeId: $this->entryTypeId,
        provisional: true,
        attributeAndFieldData: ['title' => 'Provisional Draft']
    );

    expect($response['provisional'])->toBeTrue();
    expect($response['title'])->toBe('Provisional Draft');
    expect($response['draftName'])->toBe('Draft 1');
    expect($response['_notes'])->toBe('The draft was successfully created.');
});

it('can override field data when creating from existing entry', function () {
    $canonicalId = ($this->createPublishedEntry)(['title' => 'Original Title']);
    
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    $response = $createDraft->create(
        canonicalId: $canonicalId,
        attributeAndFieldData: ['title' => 'Modified Title']
    );

    expect($response['title'])->toBe('Modified Title');
    expect($response['canonicalId'])->toBe($canonicalId);
    expect($response['_notes'])->toBe('The draft was successfully created.');
});

it('validates required parameters', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    expect(fn() => $createDraft->create())
        ->toThrow(\InvalidArgumentException::class, 'Must specify either canonicalId');
});

it('validates conflicting parameters', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    expect(fn() => $createDraft->create(
        canonicalId: 1,
        sectionId: $this->sectionId,
        entryTypeId: $this->entryTypeId
    ))->toThrow(\InvalidArgumentException::class, 'Cannot specify both canonicalId and sectionId');
});

it('validates canonical entry exists', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    
    expect(fn() => $createDraft->create(canonicalId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Entry with ID 99999 does not exist');
});

it('defaults to primary site', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;
    
    $response = $createDraft->create(
        sectionId: $this->sectionId,
        entryTypeId: $this->entryTypeId,
        attributeAndFieldData: ['title' => 'Site Test']
    );

    expect($response['siteId'])->toBe($primarySiteId);
});