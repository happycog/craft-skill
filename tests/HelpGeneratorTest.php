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

test('generateForCommand outputs full docblock', function () {
    $generator = new HelpGenerator();
    $output = $generator->generateForCommand('entries/create');

    expect($output)->toContain('Command: entries/create');
    expect($output)->toContain('Create an entry in Craft');
    expect($output)->toContain('An "Entry" in Craft is a generic term');
    expect($output)->toContain('Parameters:');
});

test('generateForCommand lists required parameters', function () {
    $generator = new HelpGenerator();
    $output = $generator->generateForCommand('entries/create');

    expect($output)->toContain('--sectionId');
    expect($output)->toContain('int, required');
    expect($output)->toContain('--entryTypeId');
});

test('generateForCommand lists optional parameters', function () {
    $generator = new HelpGenerator();
    $output = $generator->generateForCommand('entries/create');

    expect($output)->toContain('--siteId');
    expect($output)->toContain('optional');
    expect($output)->toContain('--attributeAndFieldData');
});

test('generateForCommand throws exception for unknown command', function () {
    $generator = new HelpGenerator();

    expect(fn() => $generator->generateForCommand('unknown/command'))
        ->toThrow(\InvalidArgumentException::class, 'Unknown command: unknown/command');
});

test('generateForCommand excludes @param and @return annotations from docblock', function () {
    $generator = new HelpGenerator();
    $output = $generator->generateForCommand('entries/create');

    // Should not contain @param or @return in the docblock section
    // (parameters are displayed separately in the Parameters section)
    $lines = explode("\n", $output);
    $beforeParameters = [];
    foreach ($lines as $line) {
        if (str_contains($line, 'Parameters:')) {
            break;
        }
        $beforeParameters[] = $line;
    }
    $docblockSection = implode("\n", $beforeParameters);

    expect($docblockSection)->not->toContain('@param');
    expect($docblockSection)->not->toContain('@return');
});
