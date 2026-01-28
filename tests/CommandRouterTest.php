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
