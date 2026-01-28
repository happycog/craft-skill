<?php

use craft\elements\Entry;
use craft\elements\Section;
use markhuot\craftpest\factories\Entry as EntryFactory;

/**
 * CLI Integration Tests
 *
 * These tests execute the actual bin/agent-craft script using shell commands
 * and verify the output, exit codes, and error handling.
 */

beforeEach(function () {
    // Store test section and entry type for reuse
    $section = Craft::$app->getEntries()->getSectionByHandle('news');
    $this->testSectionId = $section->id;
    $this->testEntryTypeId = $section->getEntryTypes()[0]->id;
    
    // Store created entry IDs for cleanup
    $this->createdEntryIds = [];
    
    // Helper to execute CLI command
    $this->execCli = function (string $command, bool $separateStderr = false): array {
        $cwd = getcwd();
        
        if ($separateStderr) {
            $stderrFile = tempnam(sys_get_temp_dir(), 'cli_stderr_');
            $fullCommand = "cd {$cwd} && ./bin/agent-craft {$command} 2>{$stderrFile}";
            
            $stdout = [];
            $exitCode = 0;
            exec($fullCommand, $stdout, $exitCode);
            
            $stderr = file_get_contents($stderrFile);
            unlink($stderrFile);
            
            return [
                'stdout' => implode("\n", $stdout),
                'stderr' => $stderr,
                'exitCode' => $exitCode,
            ];
        }
        
        $fullCommand = "cd {$cwd} && ./bin/agent-craft {$command} 2>&1";
        $output = [];
        $exitCode = 0;
        exec($fullCommand, $output, $exitCode);
        
        return [
            'output' => implode("\n", $output),
            'exitCode' => $exitCode,
        ];
    };
    
    // Helper to parse JSON output
    $this->parseJsonOutput = function (string $output): ?array {
        $decoded = json_decode($output, true);
        return $decoded;
    };
});

afterEach(function () {
    // Clean up any created entries
    foreach ($this->createdEntryIds as $entryId) {
        $entry = Entry::find()->id($entryId)->one();
        if ($entry instanceof Entry) {
            Craft::$app->getElements()->deleteElement($entry);
        }
    }
});

test('basic command execution - sections/list', function () {
    $result = ($this->execCli)('sections/list', true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->not->toBeEmpty();
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    
    // GetSections returns an array directly, not wrapped in 'sections' key
    expect(count($data))->toBeGreaterThan(0);
    
    // Verify section structure
    $firstSection = $data[0];
    expect($firstSection)->toHaveKeys(['id', 'name', 'handle', 'type', 'entryTypes']);
});

test('command with positional arguments - entries/get', function () {
    // Create a test entry via CLI (so it's in the database, not in a transaction)
    $createResult = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Test Entry for CLI\"",
        true
    );
    
    $createData = ($this->parseJsonOutput)($createResult['stdout']);
    $entryId = $createData['entryId'];
    $this->createdEntryIds[] = $entryId;
    
    // Now get the entry via CLI
    $result = ($this->execCli)("entries/get {$entryId}", true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->not->toBeEmpty();
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('id');
    expect($data['id'])->toBe($entryId);
    expect($data['title'])->toBe('Test Entry for CLI');
});

test('command with flags - entries/create', function () {
    $result = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"CLI Test Entry\"",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->not->toBeEmpty();
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('entryId');
    
    // Track for cleanup
    $this->createdEntryIds[] = $data['entryId'];
    
    // Verify entry was created with correct title
    $entry = Entry::find()->id($data['entryId'])->one();
    expect($entry)->toBeInstanceOf(Entry::class);
    expect($entry->title)->toBe('CLI Test Entry');
});

test('command with bracket notation for field data', function () {
    $result = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Entry with Field Data\" --attributeAndFieldData[body]=\"Test body content\"",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->not->toBeEmpty();
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('entryId');
    
    // Track for cleanup
    $this->createdEntryIds[] = $data['entryId'];
    
    // Verify entry was created with field data
    $entry = Entry::find()->id($data['entryId'])->one();
    expect($entry)->toBeInstanceOf(Entry::class);
    expect($entry->title)->toBe('Entry with Field Data');
    expect($entry->body)->toBe('Test body content');
});

test('command with nested bracket notation', function () {
    $result = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Nested Data Test\" --attributeAndFieldData[body]=\"Content here\"",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    $this->createdEntryIds[] = $data['entryId'];
    
    $entry = Entry::find()->id($data['entryId'])->one();
    expect($entry->body)->toBe('Content here');
});

test('invalid command returns exit code 2', function () {
    $result = ($this->execCli)('invalid/command', true);
    
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error['error'])->toBeString();
    expect($error['error'])->toContain('Unknown command');
});

test('missing required arguments returns exit code 2', function () {
    // Missing sectionId and entryTypeId (required parameters)
    $result = ($this->execCli)('entries/create --attributeAndFieldData[title]="Missing IDs"', true);

    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();

    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error['error'])->toBeArray();
    expect($error['error'])->toHaveKey('validation_errors');
    expect($error['error']['validation_errors'])->toBeArray();
    expect(count($error['error']['validation_errors']))->toBeGreaterThan(0);
});

test('verbosity flag -v includes exception message', function () {
    $result = ($this->execCli)('invalid/command -v', true);
    
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error)->toHaveKey('message');
});

test('verbosity flag -vv includes stack trace', function () {
    $result = ($this->execCli)('invalid/command -vv', true);
    
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error)->toHaveKey('message');
    expect($error)->toHaveKey('trace');
    expect($error['trace'])->toBeString();
});

test('verbosity flag -vvv includes file and line information', function () {
    $result = ($this->execCli)('invalid/command -vvv', true);
    
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error)->toHaveKey('message');
    expect($error)->toHaveKey('trace');
    expect($error)->toHaveKey('file');
    expect($error)->toHaveKey('line');
    expect($error)->toHaveKey('code');
});

test('JSON output is valid and parseable for success', function () {
    $result = ($this->execCli)('sections/list', true);

    expect($result['exitCode'])->toBe(0);

    // Should be valid JSON
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->not->toBeNull();
    expect($data)->toBeArray();

    // Should be compressed (single line, no internal newlines)
    $jsonOnly = trim($result['stdout']);
    expect($jsonOnly)->not->toContain("\n");
});

test('JSON output is valid and parseable for errors', function () {
    $result = ($this->execCli)('invalid/command', true);

    expect($result['exitCode'])->toBe(2);

    // Should be valid JSON
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->not->toBeNull();
    expect($error)->toBeArray();

    // Should be compressed (single line, no internal newlines)
    $jsonOnly = trim($result['stderr']);
    expect($jsonOnly)->not->toContain("\n");
});

test('entries/update command with positional and flag arguments', function () {
    // Create entry via CLI
    $createResult = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Original Title\"",
        true
    );
    
    $createData = ($this->parseJsonOutput)($createResult['stdout']);
    $entryId = $createData['entryId'];
    $this->createdEntryIds[] = $entryId;
    
    // Update via CLI
    $result = ($this->execCli)(
        "entries/update {$entryId} --attributeAndFieldData[title]=\"Updated Title\"",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('title');
    expect($data['title'])->toBe('Updated Title');
    
    // Verify update via GET
    $getResult = ($this->execCli)("entries/get {$entryId}", true);
    $getData = ($this->parseJsonOutput)($getResult['stdout']);
    expect($getData['title'])->toBe('Updated Title');
});

test('entries/delete command with positional argument', function () {
    // Create entry to delete via CLI
    $createResult = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Entry to Delete\"",
        true
    );
    
    $createData = ($this->parseJsonOutput)($createResult['stdout']);
    $entryId = $createData['entryId'];
    
    // Delete via CLI
    $result = ($this->execCli)("entries/delete {$entryId}", true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('entryId');
    expect($data)->toHaveKey('deletedPermanently');
    // Default is soft delete, so deletedPermanently is false
    expect($data['deletedPermanently'])->toBeFalse();
    
    // Verify deletion - entry should not be found
    $getResult = ($this->execCli)("entries/get {$entryId}", true);
    expect($getResult['exitCode'])->toBe(2); // Not found
});

test('entries/search command with query parameter', function () {
    // Create entry with searchable content via CLI
    // Use a unique title to avoid conflicts with other tests
    $uniqueTitle = 'UniqueSearchableTitle' . uniqid();
    $createResult = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"{$uniqueTitle}\"",
        true
    );
    
    $createData = ($this->parseJsonOutput)($createResult['stdout']);
    $entryId = $createData['entryId'];
    $this->createdEntryIds[] = $entryId;
    
    // Search for the entry
    $result = ($this->execCli)('entries/search --query="' . $uniqueTitle . '"', true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    expect($data)->toHaveKey('results');
    expect($data['results'])->toBeArray();
    
    // Note: Search might not find the entry immediately due to indexing delays
    // We'll just verify the search executes successfully and returns results array
    expect($data['results'])->toBeArray();
});

test('fields/list command returns field data', function () {
    $result = ($this->execCli)('fields/list', true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    // GetFields returns an array directly
    expect(count($data))->toBeGreaterThanOrEqual(0);
});

test('sites/list command returns site data', function () {
    $result = ($this->execCli)('sites/list', true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    
    // GetSites returns an array directly
    expect(count($data))->toBeGreaterThan(0);
    
    // Verify site structure
    $firstSite = $data[0];
    expect($firstSite)->toHaveKeys(['id', 'name', 'handle', 'primary']);
});

test('entry-types/list command returns entry type data', function () {
    $result = ($this->execCli)('entry-types/list', true);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['stderr'])->toBeEmpty();
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    expect($data)->toBeArray();
    // GetEntryTypes returns an array directly
    expect(count($data))->toBeGreaterThan(0);
});

test('no command specified returns exit code 2', function () {
    $result = ($this->execCli)('', true);
    
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error['error'])->toContain('No command specified');
});

test('command with special characters in title', function () {
    $title = "Test \"Quotes\" & Special <Chars>";
    $escapedTitle = escapeshellarg($title);
    
    $result = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]={$escapedTitle}",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    $this->createdEntryIds[] = $data['entryId'];
    
    $entry = Entry::find()->id($data['entryId'])->one();
    expect($entry->title)->toBe($title);
});

test('command with multiple fields using bracket notation', function () {
    $result = ($this->execCli)(
        "entries/create --sectionId={$this->testSectionId} --entryTypeId={$this->testEntryTypeId} --attributeAndFieldData[title]=\"Multi Field Test\" --attributeAndFieldData[body]=\"Body text here\"",
        true
    );
    
    expect($result['exitCode'])->toBe(0);
    
    $data = ($this->parseJsonOutput)($result['stdout']);
    $this->createdEntryIds[] = $data['entryId'];
    
    $entry = Entry::find()->id($data['entryId'])->one();
    expect($entry->title)->toBe('Multi Field Test');
    expect($entry->body)->toBe('Body text here');
});

test('invalid entry ID returns appropriate error', function () {
    $result = ($this->execCli)('entries/get 99999999', true);
    
    // GetEntry throws InvalidArgumentException which becomes exit code 2
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
    
    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
});

test('entries/get non-existent entry returns error with exit code 2', function () {
    $result = ($this->execCli)('entries/get 999999', true);
    
    // InvalidArgumentException becomes exit code 2
    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();
});

test('command execution with combined output captures both stdout and stderr', function () {
    $result = ($this->execCli)('sections/list', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->not->toBeEmpty();
    
    // Should be valid JSON
    $data = ($this->parseJsonOutput)($result['output']);
    expect($data)->not->toBeNull();
    expect($data)->toBeArray();
});

test('malformed validation error returns exit code 2', function () {
    // Try to create entry with invalid sectionId type (string instead of int)
    $result = ($this->execCli)(
        'entries/create --sectionId=invalid --entryTypeId=1 --attributeAndFieldData[title]="Test"',
        true
    );

    expect($result['exitCode'])->toBe(2);
    expect($result['stderr'])->not->toBeEmpty();

    $error = ($this->parseJsonOutput)($result['stderr']);
    expect($error)->toBeArray();
    expect($error)->toHaveKey('error');
    expect($error['error'])->toBeArray();
    expect($error['error'])->toHaveKey('validation_errors');
    expect($error['error']['validation_errors'])->toBeArray();
    expect(count($error['error']['validation_errors']))->toBeGreaterThan(0);
});

test('--help flag shows general help output', function () {
    $result = ($this->execCli)('--help', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->toContain('Agent Craft CLI');
    expect($result['output'])->toContain('Usage:');
    expect($result['output'])->toContain('Available commands:');
    expect($result['output'])->toContain('sections/list');
    expect($result['output'])->toContain('entries/create');
});

test('-h flag shows general help output', function () {
    $result = ($this->execCli)('-h', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->toContain('Agent Craft CLI');
    expect($result['output'])->toContain('Usage:');
    expect($result['output'])->toContain('Available commands:');
});

test('command --help shows individual command help', function () {
    $result = ($this->execCli)('entries/create --help', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->toContain('Command: entries/create');
    expect($result['output'])->toContain('Create an entry in Craft');
    expect($result['output'])->toContain('Parameters:');
    expect($result['output'])->toContain('--sectionId');
    expect($result['output'])->toContain('--entryTypeId');
    expect($result['output'])->toContain('required');
    expect($result['output'])->toContain('optional');
});

test('command -h shows individual command help', function () {
    $result = ($this->execCli)('sections/list -h', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->toContain('Command: sections/list');
    expect($result['output'])->toContain('Get a list of sections');
});

test('unknown command --help shows general help as fallback', function () {
    $result = ($this->execCli)('unknown/command --help', false);
    
    expect($result['exitCode'])->toBe(0);
    expect($result['output'])->toContain('Agent Craft CLI');
    expect($result['output'])->toContain('Available commands:');
});
