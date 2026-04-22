<?php

use CuyZ\Valinor\MapperBuilder;
use happycog\craftmcp\cli\CommandRouter;
use happycog\craftmcp\tools\CreateDraft;
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

test('routes entries/get using slug flag', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $entry = Entry::factory()
        ->section('news')
        ->slug('router-entry-slug')
        ->create();

    $result = $router->route(
        command: 'entries/get',
        positional: [],
        flags: ['slug' => 'router-entry-slug']
    );

    expect($result)->toBeArray();
    expect($result['id'])->toBe($entry->id);
    expect($result['slug'])->toBe('router-entry-slug');
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

test('routeToolClass validates inputs consistently', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $entry = Entry::factory()
        ->section('news')
        ->create();

    $result = $router->routeToolClass(
        GetEntry::class,
        positional: [],
        flags: ['entryId' => (string) $entry->id]
    );

    expect($result['id'])->toBe($entry->id);
});

test('routeToolClass accepts entryId as canonicalId alias for CreateDraft', function () {
    $mapper = (new MapperBuilder())
        ->allowPermissiveTypes()
        ->allowScalarValueCasting()
        ->argumentsMapper();

    $router = new CommandRouter($mapper);

    $entry = Entry::factory()
        ->section('news')
        ->title('Canonical Entry')
        ->create();

    $result = $router->routeToolClass(
        CreateDraft::class,
        positional: [],
        flags: ['entryId' => (string) $entry->id, 'draftName' => 'Alias Draft']
    );

    expect($result['canonicalId'])->toBe($entry->id);
    expect($result['title'])->toBe('Canonical Entry');
    expect($result['draftName'])->toBe('Alias Draft');
});
