<?php

declare(strict_types=1);

use happycog\craftmcp\llm\AnthropicDriver;
use happycog\craftmcp\llm\OpenAiDriver;
use happycog\craftmcp\llm\ToolSchemaBuilder;

// ─── ToolSchemaBuilder ──────────────────────────────────────────────

test('ToolSchemaBuilder builds tools from CommandMap', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    expect($tools)->not->toBeEmpty();
    expect($tools)->toHaveKey('SearchContent');
    expect($tools)->toHaveKey('CreateEntry');
    expect($tools)->toHaveKey('GetSections');
});

test('ToolSchemaBuilder tool has required fields', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    $tool = $tools['SearchContent'];

    expect($tool)->toHaveKey('name');
    expect($tool)->toHaveKey('description');
    expect($tool)->toHaveKey('parameters');
    expect($tool['name'])->toBe('SearchContent');
    expect($tool['description'])->toBeString()->not->toBeEmpty();
    expect($tool['parameters'])->toHaveKey('type');
    expect($tool['parameters']['type'])->toBe('object');
    expect($tool['parameters'])->toHaveKey('properties');
});

test('ToolSchemaBuilder generates correct parameter types', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    // SearchContent has: ?string $query, int $limit, string $status, ?array $sectionIds
    $params = $tools['SearchContent']['parameters'];
    $props  = (array) $params['properties'];

    expect($props)->toHaveKey('query');
    expect($props)->toHaveKey('limit');
    expect($props)->toHaveKey('status');
    expect($props)->toHaveKey('sectionIds');

    expect($props['query']['type'])->toBe('string');
    expect($props['limit']['type'])->toBe('integer');
    expect($props['limit']['default'])->toBe(5);
    expect($props['status']['type'])->toBe('string');
    // sectionIds should be array of integers (from @param array<int>|null)
    expect($props['sectionIds']['type'])->toBe('array');
    expect($props['sectionIds']['items']['type'])->toBe('integer');
});

test('ToolSchemaBuilder marks required parameters', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    // CreateEntry requires: int $sectionId, int $entryTypeId
    $params = $tools['CreateEntry']['parameters'];

    expect($params['required'])->toContain('sectionId');
    expect($params['required'])->toContain('entryTypeId');
    // Optional params should NOT be in required
    expect($params['required'])->not->toContain('siteId');
    expect($params['required'])->not->toContain('attributeAndFieldData');
});

test('ToolSchemaBuilder resolves class from tool name', function () {
    $builder = new ToolSchemaBuilder();

    expect($builder->getClass('SearchContent'))->toBe(\happycog\craftmcp\tools\SearchContent::class);
    expect($builder->getClass('CreateEntry'))->toBe(\happycog\craftmcp\tools\CreateEntry::class);
    expect($builder->getClass(ToolSchemaBuilder::TOOL_SEARCH))->toBeNull();
    expect($builder->getClass('NonExistentTool'))->toBeNull();
});

test('ToolSchemaBuilder extracts inline doc comments', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    // SearchContent has inline doc comments for $status and $sectionIds
    $props = (array) $tools['SearchContent']['parameters']['properties'];

    // These parameters have inline /** ... */ descriptions
    expect($props['status'])->toHaveKey('description');
    expect($props['status']['description'])->toContain('status');
    expect($props['sectionIds'])->toHaveKey('description');
});

test('ToolSchemaBuilder can build compact tool definitions', function () {
    $builder = new ToolSchemaBuilder();
    $tool = $builder->getTool('SearchContent', compact: true);

    expect($tool)->not->toBeNull();
    expect($tool['description'])->toBeString();
    expect(strlen($tool['description']))->toBeLessThan(200);

    $props = (array) $tool['parameters']['properties'];
    expect($props['limit'])->not->toHaveKey('default');
});

test('ToolSchemaBuilder exposes virtual ToolSearch definition', function () {
    $builder = new ToolSchemaBuilder();
    $tool = $builder->getTool(ToolSchemaBuilder::TOOL_SEARCH, compact: true);

    expect($tool)->not->toBeNull();
    expect($tool['name'])->toBe(ToolSchemaBuilder::TOOL_SEARCH);
    expect($tool['parameters']['type'])->toBe('object');

    $props = (array) $tool['parameters']['properties'];
    expect($props)->toHaveKey('query');
    expect($props)->toHaveKey('names');
    expect($props)->toHaveKey('limit');
});

test('ToolSchemaBuilder searches tools and returns revealed tool names', function () {
    $builder = new ToolSchemaBuilder();
    $result = $builder->searchTools('section', limit: 5);

    expect($result)->toHaveKey('revealedTools');
    expect($result)->toHaveKey('matches');
    expect($result['revealedTools'])->not->toBeEmpty();
    expect($result['matches'][0])->toHaveKey('name');
    expect($result['matches'][0])->toHaveKey('parameters');
});

// ─── Anthropic message conversion ───────────────────────────────────

test('AnthropicDriver converts user messages', function () {
    // Access private method via reflection
    $driver = new AnthropicDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        ['role' => 'user', 'content' => 'Hello world'],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('user');
    expect($result[0]['content'])->toBe('Hello world');
});

test('AnthropicDriver converts assistant messages with tool calls', function () {
    $driver = new AnthropicDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        [
            'role'      => 'assistant',
            'content'   => 'Let me search.',
            'toolCalls' => [
                ['id' => 'call_1', 'name' => 'SearchContent', 'input' => ['query' => 'blog']],
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('assistant');
    expect($result[0]['content'])->toHaveCount(2); // text + tool_use

    $textBlock = $result[0]['content'][0];
    expect($textBlock['type'])->toBe('text');
    expect($textBlock['text'])->toBe('Let me search.');

    $toolBlock = $result[0]['content'][1];
    expect($toolBlock['type'])->toBe('tool_use');
    expect($toolBlock['id'])->toBe('call_1');
    expect($toolBlock['name'])->toBe('SearchContent');
});

test('AnthropicDriver converts tool results into user messages', function () {
    $driver = new AnthropicDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        ['role' => 'tool', 'toolCallId' => 'call_1', 'name' => 'SearchContent', 'content' => '{"results":[]}'],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('user');
    expect($result[0]['content'])->toHaveCount(1);
    expect($result[0]['content'][0]['type'])->toBe('tool_result');
    expect($result[0]['content'][0]['tool_use_id'])->toBe('call_1');
});

test('AnthropicDriver batches consecutive tool results', function () {
    $driver = new AnthropicDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        ['role' => 'tool', 'toolCallId' => 'call_1', 'name' => 'SearchContent', 'content' => '{}'],
        ['role' => 'tool', 'toolCallId' => 'call_2', 'name' => 'GetEntry', 'content' => '{}'],
    ]);

    // Both should be in a single user message
    expect($result)->toHaveCount(1);
    expect($result[0]['content'])->toHaveCount(2);
});

test('AnthropicDriver converts tool definitions', function () {
    $driver = new AnthropicDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertTools');

    $result = $method->invoke($driver, [
        [
            'name'        => 'SearchContent',
            'description' => 'Search for content.',
            'parameters'  => ['type' => 'object', 'properties' => new \stdClass()],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0])->toHaveKey('name');
    expect($result[0])->toHaveKey('description');
    expect($result[0])->toHaveKey('input_schema');
    expect($result[0]['name'])->toBe('SearchContent');
});

// ─── OpenAI message conversion ──────────────────────────────────────

test('OpenAiDriver converts user messages', function () {
    $driver = new OpenAiDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        ['role' => 'user', 'content' => 'Hello world'],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('user');
    expect($result[0]['content'])->toBe('Hello world');
});

test('OpenAiDriver converts assistant messages with tool calls', function () {
    $driver = new OpenAiDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        [
            'role'      => 'assistant',
            'content'   => 'Searching.',
            'toolCalls' => [
                ['id' => 'call_1', 'name' => 'SearchContent', 'input' => ['query' => 'blog']],
            ],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('assistant');
    expect($result[0]['content'])->toBe('Searching.');
    expect($result[0]['tool_calls'])->toHaveCount(1);
    expect($result[0]['tool_calls'][0]['id'])->toBe('call_1');
    expect($result[0]['tool_calls'][0]['type'])->toBe('function');
    expect($result[0]['tool_calls'][0]['function']['name'])->toBe('SearchContent');
    expect($result[0]['tool_calls'][0]['function']['arguments'])->toBe('{"query":"blog"}');
});

test('OpenAiDriver converts tool results', function () {
    $driver = new OpenAiDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertMessages');

    $result = $method->invoke($driver, [
        ['role' => 'tool', 'toolCallId' => 'call_1', 'name' => 'SearchContent', 'content' => '{}'],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['role'])->toBe('tool');
    expect($result[0]['tool_call_id'])->toBe('call_1');
});

test('OpenAiDriver converts tool definitions', function () {
    $driver = new OpenAiDriver(apiKey: 'test-key');
    $method = new ReflectionMethod($driver, 'convertTools');

    $result = $method->invoke($driver, [
        [
            'name'        => 'SearchContent',
            'description' => 'Search for content.',
            'parameters'  => ['type' => 'object', 'properties' => new \stdClass()],
        ],
    ]);

    expect($result)->toHaveCount(1);
    expect($result[0]['type'])->toBe('function');
    expect($result[0]['function']['name'])->toBe('SearchContent');
    expect($result[0]['function']['description'])->toBe('Search for content.');
    expect($result[0]['function'])->toHaveKey('parameters');
});
