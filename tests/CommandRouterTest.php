<?php

use CuyZ\Valinor\MapperBuilder;
use happycog\craftmcp\cli\CommandRouter;
use happycog\craftmcp\tools\GetEntry;
use markhuot\craftpest\factories\Entry;

test('routes commands to correct tool methods', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    // Create a test entry
    $entry = Entry::factory()
        ->section('news')
        ->create();

    // Test routing to GetEntry::get
    $result = $router->route(
        command: 'entries/get',
        positional: [$entry->id],
        flags: []
    );

    expect($result)->toBeArray();
    expect($result)->toHaveKey('id');
    expect($result['id'])->toBe($entry->id);
});

test('throws exception for unknown commands', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $router->route(
        command: 'unknown/command',
        positional: [],
        flags: []
    );
})->throws(\InvalidArgumentException::class, 'Unknown command: unknown/command');

test('merges positional and flag arguments', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    // Test with SearchContent which has optional parameters
    $result = $router->route(
        command: 'entries/search',
        positional: [], // No positional args
        flags: ['query' => 'test', 'limit' => 10]
    );

    expect($result)->toBeArray();
    expect($result)->toHaveKey('results');
});

test('maps positional arguments to parameter names', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $entry = Entry::factory()
        ->section('news')
        ->create();

    // Test positional argument mapping (entryId is first parameter)
    $result = $router->route(
        command: 'entries/get',
        positional: [$entry->id],
        flags: []
    );

    expect($result)->toBeArray();
    expect($result['id'])->toBe($entry->id);
});

test('supports entry-types list command', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $result = $router->route(
        command: 'entry-types/list',
        positional: [],
        flags: []
    );

    expect($result)->toBeArray();
});

test('supports sections list command', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $result = $router->route(
        command: 'sections/list',
        positional: [],
        flags: []
    );

    expect($result)->toBeArray();
});

test('resolves file references in flag arguments', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    // Create a temporary JSON file
    $tempFile = tempnam(sys_get_temp_dir(), 'test_');
    $jsonFile = $tempFile . '.json';
    rename($tempFile, $jsonFile);
    file_put_contents($jsonFile, json_encode([
        'title' => 'Test Entry',
        'slug' => 'test-entry'
    ]));

    try {
        // Create section and entry type for testing
        $section = \markhuot\craftpest\factories\Section::factory()
            ->type('single')
            ->create();
        $entryType = $section->getEntryTypes()[0];

        // Use basename since file is in temp dir, not cwd
        // We'll use the actual file path by temporarily changing directory
        $originalCwd = getcwd();
        chdir(sys_get_temp_dir());

        $result = $router->route(
            command: 'entries/create',
            positional: [],
            flags: [
                'sectionId' => $section->id,
                'entryTypeId' => $entryType->id,
                'attributeAndFieldData' => ['__file__' => basename($jsonFile)]
            ]
        );

        chdir($originalCwd);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('entryId');
        expect($result['title'])->toBe('Test Entry');
        expect($result['slug'])->toBe('test-entry');
    } finally {
        unlink($jsonFile);
        if (isset($originalCwd)) {
            chdir($originalCwd);
        }
    }
});

test('throws exception when file reference does not exist', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $section = \markhuot\craftpest\factories\Section::factory()
        ->type('single')
        ->create();
    $entryType = $section->getEntryTypes()[0];

    $router->route(
        command: 'entries/create',
        positional: [],
        flags: [
            'sectionId' => $section->id,
            'entryTypeId' => $entryType->id,
            'attributeAndFieldData' => ['__file__' => 'nonexistent.json']
        ]
    );
})->throws(\InvalidArgumentException::class, 'File not found: nonexistent.json');

test('throws exception when file contains invalid JSON', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    // Create a temporary file with invalid JSON
    $tempFile = tempnam(sys_get_temp_dir(), 'test_');
    $jsonFile = $tempFile . '.json';
    rename($tempFile, $jsonFile);
    file_put_contents($jsonFile, '{invalid json}');

    try {
        $section = \markhuot\craftpest\factories\Section::factory()
            ->type('single')
            ->create();
        $entryType = $section->getEntryTypes()[0];

        $originalCwd = getcwd();
        chdir(sys_get_temp_dir());

        $router->route(
            command: 'entries/create',
            positional: [],
            flags: [
                'sectionId' => $section->id,
                'entryTypeId' => $entryType->id,
                'attributeAndFieldData' => ['__file__' => basename($jsonFile)]
            ]
        );
    } finally {
        unlink($jsonFile);
        if (isset($originalCwd)) {
            chdir($originalCwd);
        }
    }
})->throws(\InvalidArgumentException::class);
