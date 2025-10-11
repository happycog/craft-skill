<?php

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\DeleteEntryType;

beforeEach(function () {
    // Clean up any existing test entry types
    $entriesService = Craft::$app->getEntries();
    $testHandles = [
        'testDeleteEntryType', 'entryTypeWithEntries', 'emptyEntryType'
    ];
    
    foreach ($testHandles as $handle) {
        $entryType = $entriesService->getEntryTypeByHandle($handle);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
    
    // Track created entry types for cleanup
    $this->createdEntryTypeIds = [];
    
    $this->createEntryType = function (string $name, array $options = []) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);
        
        $result = $createEntryType->create(
            name: $name,
            handle: $options['handle'] ?? null,
            hasTitleField: $options['hasTitleField'] ?? true,
            titleTranslationMethod: $options['titleTranslationMethod'] ?? 'site',
            titleTranslationKeyFormat: $options['titleTranslationKeyFormat'] ?? null,
            icon: $options['icon'] ?? null,
            color: $options['color'] ?? null
        );
        
        $this->createdEntryTypeIds[] = $result['entryTypeId'];
        return $result;
    };
    
    $this->deleteEntryType = function (int $entryTypeId, bool $force = false) {
        $deleteEntryType = Craft::$container->get(DeleteEntryType::class);
        
        return $deleteEntryType->delete(
            entryTypeId: $entryTypeId,
            force: $force
        );
    };
});

afterEach(function () {
    // Clean up any remaining entry types
    $entriesService = Craft::$app->getEntries();
    
    foreach ($this->createdEntryTypeIds ?? [] as $entryTypeId) {
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if ($entryType) {
            $entriesService->deleteEntryType($entryType);
        }
    }
});

it('can delete an empty entry type', function () {
    $created = ($this->createEntryType)('Test Delete Entry Type', ['handle' => 'testDeleteEntryType']);
    
    $result = ($this->deleteEntryType)($created['entryTypeId']);
    
    expect($result['deleted'])->toBeTrue();
    expect($result['entryType']['id'])->toBe($created['entryTypeId']);
    expect($result['entryType']['name'])->toBe('Test Delete Entry Type');
    expect($result['entryType']['handle'])->toBe('testDeleteEntryType');
    expect($result['usageStats']['total'])->toBe(0);
    expect($result['forced'])->toBeFalse();
    expect($result['_notes'])->toContain('successfully deleted');
    
    // Verify entry type was actually deleted
    $entryType = Craft::$app->getEntries()->getEntryTypeById($created['entryTypeId']);
    expect($entryType)->toBeNull();
    
    // Remove from tracking since it's deleted
    $this->createdEntryTypeIds = array_filter($this->createdEntryTypeIds, function($id) use ($created) {
        return $id !== $created['entryTypeId'];
    });
});

it('throws exception for non-existent entry type', function () {
    expect(fn() => ($this->deleteEntryType)(99999))
        ->toThrow(\InvalidArgumentException::class, 'Entry type with ID 99999 not found');
});

it('returns proper usage statistics for empty entry type', function () {
    $created = ($this->createEntryType)('Empty Entry Type', ['handle' => 'emptyEntryType']);
    
    $result = ($this->deleteEntryType)($created['entryTypeId']);
    
    expect($result['usageStats'])->toHaveKeys(['entries', 'drafts', 'revisions', 'total']);
    expect($result['usageStats']['entries'])->toBe(0);
    expect($result['usageStats']['drafts'])->toBe(0);
    expect($result['usageStats']['revisions'])->toBe(0);
    expect($result['usageStats']['total'])->toBe(0);
    
    // Remove from tracking since it's deleted
    $this->createdEntryTypeIds = array_filter($this->createdEntryTypeIds, function($id) use ($created) {
        return $id !== $created['entryTypeId'];
    });
});

it('includes entry type information in response', function () {
    $created = ($this->createEntryType)('Info Test Entry Type');
    
    $result = ($this->deleteEntryType)($created['entryTypeId']);
    
    expect($result['entryType'])->toHaveKeys(['id', 'name', 'handle', 'fieldLayoutId']);
    expect($result['entryType']['id'])->toBe($created['entryTypeId']);
    expect($result['entryType']['name'])->toBe('Info Test Entry Type');
    expect($result['entryType']['fieldLayoutId'])->not->toBeNull();
    
    // Remove from tracking since it's deleted
    $this->createdEntryTypeIds = array_filter($this->createdEntryTypeIds, function($id) use ($created) {
        return $id !== $created['entryTypeId'];
    });
});

it('returns all expected response fields', function () {
    $created = ($this->createEntryType)('Response Test Entry Type');
    
    $result = ($this->deleteEntryType)($created['entryTypeId']);
    
    expect($result)->toHaveKeys([
        '_notes',
        'deleted',
        'entryType',
        'usageStats',
        'forced'
    ]);
    
    // Remove from tracking since it's deleted
    $this->createdEntryTypeIds = array_filter($this->createdEntryTypeIds, function($id) use ($created) {
        return $id !== $created['entryTypeId'];
    });
});

// Note: Testing with existing entries would require creating entries, which is complex in this test environment
// The force deletion functionality is tested through the parameters, but actual usage stats would be 0
// in this test environment since we're not creating actual entries with content.

it('accepts force parameter correctly', function () {
    $created = ($this->createEntryType)('Force Test Entry Type');
    
    // This should work the same as normal deletion since there are no entries
    $result = ($this->deleteEntryType)($created['entryTypeId'], true);
    
    expect($result['deleted'])->toBeTrue();
    expect($result['forced'])->toBeFalse(); // Should be false since no entries existed
    
    // Remove from tracking since it's deleted
    $this->createdEntryTypeIds = array_filter($this->createdEntryTypeIds, function($id) use ($created) {
        return $id !== $created['entryTypeId'];
    });
});