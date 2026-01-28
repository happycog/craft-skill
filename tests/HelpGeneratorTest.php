<?php

use happycog\craftmcp\cli\HelpGenerator;

test('generates help output with header', function () {
    $generator = new HelpGenerator();
    $output = $generator->generate();

    expect($output)->toContain('Agent Craft CLI');
    expect($output)->toContain('Usage:');
    expect($output)->toContain('Available commands:');
});

test('includes all commands in help output', function () {
    $generator = new HelpGenerator();
    $output = $generator->generate();

    expect($output)->toContain('sections/list');
    expect($output)->toContain('entries/create');
    expect($output)->toContain('fields/list');
    expect($output)->toContain('assets/create');
    expect($output)->toContain('drafts/apply');
});

test('includes help flag documentation', function () {
    $generator = new HelpGenerator();
    $output = $generator->generate();

    expect($output)->toContain('-h, --help');
    expect($output)->toContain('--path=');
});

test('getCommandDescriptions returns all commands', function () {
    $generator = new HelpGenerator();
    $commands = $generator->getCommandDescriptions();

    expect($commands)->toHaveKey('sections/list');
    expect($commands)->toHaveKey('entries/create');
    expect($commands)->toHaveKey('entries/get');
    expect($commands)->toHaveKey('fields/list');
});

test('getCommandDescriptions extracts first line from docblocks', function () {
    $generator = new HelpGenerator();
    $commands = $generator->getCommandDescriptions();

    // sections/list should start with "Get a list of sections"
    expect($commands['sections/list'])->toContain('sections');

    // entries/create should start with "Create an entry"
    expect($commands['entries/create'])->toContain('entry');
});

test('commands are sorted alphabetically', function () {
    $generator = new HelpGenerator();
    $commands = $generator->getCommandDescriptions();

    $keys = array_keys($commands);
    $sortedKeys = $keys;
    sort($sortedKeys);

    expect($keys)->toBe($sortedKeys);
});
