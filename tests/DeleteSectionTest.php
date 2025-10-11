<?php

use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\DeleteSection;
use craft\models\Section;

beforeEach(function () {
    // Use microtime to ensure unique handles across all tests
    $this->uniqueId = str_replace('.', '', microtime(true));

    // Helper to create a test entry type for use in sections
    $this->createTestEntryType = function (?string $name = null): array {
        if ($name === null) {
            $name = 'Test Entry Type ' . $this->uniqueId . mt_rand(1000, 9999);
        }
        $tool = new CreateEntryType();
        return $tool->create($name);
    };

    // Helper to create a test section
    $this->createTestSection = function (string $name = 'Test Section', string $type = 'channel', array $entryTypeIds = null): array {
        $entryTypes = [];
        if ($entryTypeIds === null) {
            $entryType = ($this->createTestEntryType)();
            $entryTypeIds = [$entryType['entryTypeId']];
            $entryTypes = [$entryType];
        }

        $tool = new CreateSection();
        $sectionData = $tool->create($name, $type, $entryTypeIds);

        // Merge section data with entry types for convenience
        $sectionData['entryTypes'] = $entryTypes;
        return $sectionData;
    };

    // Helper to create a test entry
    $this->createTestEntry = function (int $sectionId, int $entryTypeId, string $title = 'Test Entry'): array {
        $tool = new CreateEntry();
        return $tool->create($sectionId, $entryTypeId, null, ['title' => $title]);
    };
});

test('delete section tool schema is valid', function () {
    $tool = new DeleteSection();
    $reflection = new ReflectionClass($tool);
    $method = $reflection->getMethod('delete');
    $attributes = $method->getAttributes(\PhpMcp\Server\Attributes\McpTool::class);
    
    expect($attributes)->toHaveCount(1);
    
    // Verify the McpTool attribute has the correct name
    $mcpToolAttribute = $attributes[0]->newInstance();
    expect($mcpToolAttribute->name)->toBe('delete_section');
});

test('deletes empty section successfully', function () {
    // Create a section without any entries
    $sectionName = 'Delete Test Empty ' . $this->uniqueId;
    $section = ($this->createTestSection)($sectionName);

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe($sectionName)
        ->and($result['handle'])->toBe($section['handle'])
        ->and($result['impact']['hasContent'])->toBeFalse()
        ->and($result['impact']['entryCount'])->toBe(0)
        ->and($result['impact']['draftCount'])->toBe(0)
        ->and($result['impact']['revisionCount'])->toBe(0);
});

test('prevents deletion of section with entries without force', function () {
    // Create a section and add an entry
    $sectionName = 'Delete Test With Entries ' . $this->uniqueId;
    $section = ($this->createTestSection)($sectionName);
    $entryType = $section['entryTypes'][0] ?? null;
    expect($entryType)->not->toBeNull();

    $entry = ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Test Entry');

    $tool = new DeleteSection();

    expect(fn() => $tool->delete($section['sectionId']))
        ->toThrow(RuntimeException::class, "Section '{$sectionName}' contains data and cannot be deleted without force=true");
});

test('deletes section with entries when force is true', function () {
    // Create a section and add an entry
    $sectionName = 'Delete Test Force ' . $this->uniqueId;
    $section = ($this->createTestSection)($sectionName);
    $entryType = $section['entryTypes'][0] ?? null;
    expect($entryType)->not->toBeNull();

    $entry = ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Test Entry for Force Delete');

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId'], true);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($section['sectionId'])
        ->and($result['name'])->toBe($sectionName)
        ->and($result['impact']['hasContent'])->toBeTrue()
        ->and($result['impact']['entryCount'])->toBeGreaterThan(0);
});

test('provides detailed impact assessment', function () {
    // Create a section and add multiple entries
    $sectionName = 'Delete Test Impact ' . $this->uniqueId;
    $section = ($this->createTestSection)($sectionName);
    $entryType = $section['entryTypes'][0] ?? null;
    expect($entryType)->not->toBeNull();

    // Add multiple entries
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Entry 1');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Entry 2');

    $tool = new DeleteSection();

    expect(fn() => $tool->delete($section['sectionId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $section) {
            try {
                $tool->delete($section['sectionId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain('Impact Assessment:')
                    ->and($message)->toContain('Entries:')
                    ->and($message)->toContain('Drafts:')
                    ->and($message)->toContain('Revisions:')
                    ->and($message)->toContain('Entry Types:');
            }
        });
});

test('fails when section does not exist', function () {
    $tool = new DeleteSection();

    expect(fn() => $tool->delete(99999))
        ->toThrow(RuntimeException::class, 'Section with ID 99999 not found');
});

test('deletes single section type', function () {
    $section = ($this->createTestSection)('Single Section Delete ' . $this->uniqueId, 'single');

    $tool = new DeleteSection();
    // Single sections may have auto-created entries, use force if needed
    $result = $tool->delete($section['sectionId'], true);

    expect($result)->toBeArray()
        ->and($result['type'])->toBe(Section::TYPE_SINGLE);
});

test('deletes channel section type', function () {
    $section = ($this->createTestSection)('Channel Section Delete ' . $this->uniqueId, 'channel');

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result)->toBeArray()
        ->and($result['type'])->toBe(Section::TYPE_CHANNEL);
});

test('deletes structure section type', function () {
    $section = ($this->createTestSection)('Structure Section Delete ' . $this->uniqueId, 'structure');

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result)->toBeArray()
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE);
});

test('analyzes impact correctly for empty section', function () {
    $section = ($this->createTestSection)('Empty Impact Test ' . $this->uniqueId);

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result['impact'])->toBeArray()
        ->and($result['impact']['hasContent'])->toBeFalse()
        ->and($result['impact']['entryCount'])->toBe(0)
        ->and($result['impact']['draftCount'])->toBe(0)
        ->and($result['impact']['revisionCount'])->toBe(0)
        ->and($result['impact']['entryTypeCount'])->toBeGreaterThan(0)
        ->and($result['impact']['entryTypes'])->toBeArray();
});

test('includes entry type information in impact', function () {
    $entryType = ($this->createTestEntryType)();
    $section = ($this->createTestSection)('Impact Entry Type Test ' . $this->uniqueId, 'channel', [$entryType['entryTypeId']]);

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result['impact']['entryTypes'])->toBeArray()
        ->and($result['impact']['entryTypes'][0])->toHaveKeys(['id', 'name', 'handle'])
        ->and($result['impact']['entryTypes'][0]['name'])->toBe($entryType['name']);
});

test('handles section with multiple entry types', function () {
    // Create multiple entry types
    $entryType1 = ($this->createTestEntryType)();
    $entryType2 = ($this->createTestEntryType)();

    $section = ($this->createTestSection)('Multi Type Section ' . $this->uniqueId, 'channel', [
        $entryType1['entryTypeId'],
        $entryType2['entryTypeId']
    ]);

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result['impact']['entryTypeCount'])->toBe(2)
        ->and($result['impact']['entryTypes'])->toHaveCount(2);
});

test('force parameter validation', function () {
    $section = ($this->createTestSection)('Force Validation Test ' . $this->uniqueId);

    $tool = new DeleteSection();

    // Test with valid force values
    expect($tool->delete($section['sectionId'], false))->toBeArray();

    // Create new section for second test
    $section2 = ($this->createTestSection)('Force Validation Test 2 ' . $this->uniqueId);
    expect($tool->delete($section2['sectionId'], true))->toBeArray();
});

test('error message includes section name and details', function () {
    $sectionName = 'Error Message Test ' . $this->uniqueId;
    $section = ($this->createTestSection)($sectionName);
    $entryType = $section['entryTypes'][0] ?? null;
    expect($entryType)->not->toBeNull();

    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Error Test Entry');

    $tool = new DeleteSection();

    expect(fn() => $tool->delete($section['sectionId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $section, $sectionName) {
            try {
                $tool->delete($section['sectionId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain($sectionName)
                    ->and($message)->toContain('force=true')
                    ->and($message)->toContain('This action cannot be undone');
            }
        });
});

test('successful deletion includes complete information', function () {
    $section = ($this->createTestSection)('Complete Info Test ' . $this->uniqueId);

    $tool = new DeleteSection();
    $result = $tool->delete($section['sectionId']);

    expect($result)->toHaveKeys(['id', 'name', 'handle', 'type', 'impact'])
        ->and($result['id'])->toBeInt()
        ->and($result['name'])->toBeString()
        ->and($result['handle'])->toBeString()
        ->and($result['type'])->toBeString()
        ->and($result['impact'])->toBeArray();
});

test('handles sections created with different settings', function () {
    // Test with different section configurations
    $entryType = ($this->createTestEntryType)();

    // Create section with custom settings
    $tool = new CreateSection();
    $section = $tool->create(
        name: 'Custom Settings Section',
        type: 'structure',
        entryTypeIds: [$entryType['entryTypeId']],
        handle: 'customSettingsSection',
        enableVersioning: false,
        maxLevels: 5
    );

    $deleteJob = new DeleteSection();
    $result = $deleteJob->delete($section['sectionId']);

    expect($result['name'])->toBe('Custom Settings Section')
        ->and($result['handle'])->toBe('customSettingsSection')
        ->and($result['type'])->toBe(Section::TYPE_STRUCTURE);
});

test('impact assessment counts are accurate', function () {
    $section = ($this->createTestSection)('Accurate Count Test ' . $this->uniqueId);
    $entryType = $section['entryTypes'][0] ?? null;
    expect($entryType)->not->toBeNull();

    // Add known number of entries
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Count Entry 1');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Count Entry 2');
    ($this->createTestEntry)($section['sectionId'], $entryType['entryTypeId'], 'Count Entry 3');

    $tool = new DeleteSection();

    expect(fn() => $tool->delete($section['sectionId']))
        ->toThrow(RuntimeException::class)
        ->and(function () use ($tool, $section) {
            try {
                $tool->delete($section['sectionId']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
                expect($message)->toContain('Entries: 3');
            }
        });
});
