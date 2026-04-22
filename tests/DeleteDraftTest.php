<?php

use craft\elements\Entry;
use happycog\craftmcp\tools\CreateDraft;
use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\DeleteDraft;

beforeEach(function () {
    $this->section = Craft::$app->getEntries()->getSectionByHandle('news');
    expect($this->section)->not->toBeNull();
    $this->sectionId = $this->section->id;
    $this->entryTypeId = $this->section->getEntryTypes()[0]->id;

    $this->createPublishedEntry = function (array $attributeAndFieldData = []) {
        $createEntry = Craft::$container->get(CreateEntry::class);
        $response = $createEntry->__invoke(
            sectionId: $this->sectionId,
            entryTypeId: $this->entryTypeId,
            attributeAndFieldData: array_merge(['title' => 'Canonical Entry'], $attributeAndFieldData)
        );

        return $response['entryId'];
    };

    $this->createDraftFromEntry = function (int $canonicalId, array $attributeAndFieldData = []) {
        $createDraft = Craft::$container->get(CreateDraft::class);
        $response = $createDraft->__invoke(
            canonicalId: $canonicalId,
            attributeAndFieldData: $attributeAndFieldData
        );

        return $response['draftId'];
    };
});

it('can soft delete a draft without changing its canonical entry', function () {
    $canonicalId = ($this->createPublishedEntry)(['title' => 'Original Canonical Title']);
    $draftId = ($this->createDraftFromEntry)($canonicalId, ['title' => 'Draft Title']);

    $response = Craft::$container->get(DeleteDraft::class)->__invoke(
        draftId: $draftId,
    );

    expect($response['draftId'])->toBe($draftId);
    expect($response['canonicalId'])->toBe($canonicalId);
    expect($response['deletedPermanently'])->toBeFalse();

    $deletedDraft = Entry::find()->id($draftId)->drafts()->trashed()->one();
    expect($deletedDraft)->toBeInstanceOf(Entry::class);
    expect($deletedDraft->trashed)->toBeTrue();

    $canonicalEntry = Entry::find()->id($canonicalId)->one();
    expect($canonicalEntry)->toBeInstanceOf(Entry::class);
    expect($canonicalEntry->title)->toBe('Original Canonical Title');
});

it('can permanently delete a draft without changing its canonical entry', function () {
    $canonicalId = ($this->createPublishedEntry)(['title' => 'Permanent Delete Canonical']);
    $draftId = ($this->createDraftFromEntry)($canonicalId, ['title' => 'Temporary Draft']);

    $response = Craft::$container->get(DeleteDraft::class)->__invoke(
        draftId: $draftId,
        permanentlyDelete: true,
    );

    expect($response['draftId'])->toBe($draftId);
    expect($response['deletedPermanently'])->toBeTrue();

    expect(Entry::find()->id($draftId)->drafts()->trashed()->one())->toBeNull();

    $canonicalEntry = Entry::find()->id($canonicalId)->one();
    expect($canonicalEntry)->toBeInstanceOf(Entry::class);
    expect($canonicalEntry->title)->toBe('Permanent Delete Canonical');
});

it('keeps the canonical entry created for from-scratch drafts', function () {
    $createDraft = Craft::$container->get(CreateDraft::class);
    $draftResponse = $createDraft->__invoke(
        sectionId: $this->sectionId,
        entryTypeId: $this->entryTypeId,
        attributeAndFieldData: ['title' => 'Scratch Draft Title']
    );

    $response = Craft::$container->get(DeleteDraft::class)->__invoke(
        draftId: $draftResponse['draftId'],
        permanentlyDelete: true,
    );

    expect($response['canonicalId'])->toBe($draftResponse['canonicalId']);

    $canonicalEntry = Entry::find()->id($draftResponse['canonicalId'])->one();
    expect($canonicalEntry)->toBeInstanceOf(Entry::class);
    expect($canonicalEntry->title)->toBe('Scratch Draft Title');
});

it('returns the expected response shape', function () {
    $canonicalId = ($this->createPublishedEntry)();
    $draftId = ($this->createDraftFromEntry)($canonicalId, ['title' => 'Response Draft']);

    $response = Craft::$container->get(DeleteDraft::class)->__invoke($draftId);

    expect($response)->toHaveKeys([
        '_notes',
        'draftId',
        'canonicalId',
        'title',
        'slug',
        'draftName',
        'draftNotes',
        'provisional',
        'siteId',
        'deletedPermanently',
    ]);
});

it('throws error when the draft does not exist', function () {
    expect(fn() => Craft::$container->get(DeleteDraft::class)->__invoke(99999))
        ->toThrow(\InvalidArgumentException::class, 'Draft with ID 99999 does not exist');
});

it('throws error when trying to delete a published entry as a draft', function () {
    $canonicalId = ($this->createPublishedEntry)();

    expect(fn() => Craft::$container->get(DeleteDraft::class)->__invoke($canonicalId))
        ->toThrow(\InvalidArgumentException::class, 'Entry with ID ' . $canonicalId . ' is not a draft');
});
