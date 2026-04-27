<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use happycog\craftmcp\llm\AnthropicDriver;
use happycog\craftmcp\llm\HttpHeartbeat;
use happycog\craftmcp\llm\OpenAiDriver;
use happycog\craftmcp\llm\OpenCodeDriver;
use happycog\craftmcp\llm\ToolSchemaBuilder;

// ─── ToolSchemaBuilder ──────────────────────────────────────────────

test('ToolSchemaBuilder builds tools from CommandMap', function () {
    $builder = new ToolSchemaBuilder();
    $tools   = $builder->getTools();

    expect($tools)->not->toBeEmpty();
    expect($tools)->toHaveKey('SearchContent');
    expect($tools)->toHaveKey('CreateEntry');
    expect($tools)->toHaveKey('GetSections');
    expect($tools)->not->toHaveKey('OpenUrl');
});

test('ToolSchemaBuilder can include chat-only tools when requested', function () {
    $builder = new ToolSchemaBuilder();
    $tools = $builder->getTools(includeChatOnly: true);

    expect($tools)->toHaveKey('OpenUrl');
    expect($builder->getClass('OpenUrl'))->toBe(\happycog\craftmcp\tools\OpenUrl::class);
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

test('ToolSchemaBuilder can build minimal tool definitions', function () {
    $builder = new ToolSchemaBuilder();
    $tools = $builder->getTools(minimal: true);
    $tool = $tools['SearchContent'];

    expect($tool)->not->toBeNull();
    expect($tool['name'])->toBe('SearchContent');
    expect($tool['description'])->toBe('');
    expect($tool['parameters'])->toHaveKey('type', 'object');
    expect($tool['parameters'])->toHaveKey('properties');
    expect((array) $tool['parameters']['properties'])->toBe([]);
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

test('ToolSchemaBuilder exposes compact input schema summaries', function () {
    $builder = new ToolSchemaBuilder();
    $schema = $builder->getToolInputSchema('CreateDraft');

    expect($schema)->not->toBeNull();
    expect($schema['type'])->toBe('object');
    expect($schema['properties'])->toBeArray();
    expect($schema['properties'][0])->toHaveKeys(['name', 'type', 'required']);

    $propertyNames = array_column($schema['properties'], 'name');
    expect($propertyNames)->toContain('sectionId');
    expect($propertyNames)->toContain('entryTypeId');
    expect($propertyNames)->toContain('canonicalId');
});

test('ToolSchemaBuilder hides chat-only tools from search unless requested', function () {
    $builder = new ToolSchemaBuilder();

    $defaultResult = $builder->searchTools('open url', limit: 5);
    $chatResult = $builder->searchTools('open url', limit: 5, includeChatOnly: true);

    expect($defaultResult['revealedTools'])->not->toContain('OpenUrl');
    expect($chatResult['revealedTools'])->toContain('OpenUrl');
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

// ─── OpenCode driver ────────────────────────────────────────────────

test('OpenCodeDriver passes a single user message through verbatim', function () {
    $driver = new OpenCodeDriver();
    $method = new ReflectionMethod($driver, 'buildPrompt');

    $result = $method->invoke($driver, [
        ['role' => 'user', 'content' => 'What sections do I have?'],
    ]);

    expect($result)->toBe('What sections do I have?');
});

test('OpenCodeDriver flattens multi-turn history into a transcript', function () {
    $driver = new OpenCodeDriver();
    $method = new ReflectionMethod($driver, 'buildPrompt');

    $result = $method->invoke($driver, [
        ['role' => 'user', 'content' => 'Hi'],
        ['role' => 'assistant', 'content' => 'Hello!'],
        ['role' => 'user', 'content' => 'What can you do?'],
    ]);

    expect($result)->toContain('Conversation history:');
    expect($result)->toContain('User: Hi');
    expect($result)->toContain('Assistant: Hello!');
    expect($result)->toContain('User: What can you do?');
    expect($result)->toContain('Please respond to the most recent user message above.');
});

test('OpenCodeDriver renders tool calls and results in the transcript', function () {
    $driver = new OpenCodeDriver();
    $method = new ReflectionMethod($driver, 'buildPrompt');

    $result = $method->invoke($driver, [
        ['role' => 'user', 'content' => 'Find blog posts'],
        [
            'role'      => 'assistant',
            'content'   => 'Searching.',
            'toolCalls' => [
                ['id' => 'call_1', 'name' => 'SearchContent', 'input' => ['query' => 'blog']],
            ],
        ],
        ['role' => 'tool', 'toolCallId' => 'call_1', 'name' => 'SearchContent', 'content' => '{"results":[]}'],
        ['role' => 'user', 'content' => 'Anything else?'],
    ]);

    expect($result)->toContain('Assistant: Searching.');
    expect($result)->toContain('[called tool SearchContent with {"query":"blog"}]');
    expect($result)->toContain('[tool SearchContent returned]: {"results":[]}');
});

test('OpenCodeDriver creates a session and posts the user message to it', function () {
    /** @var array<int, Request> $requests */
    $requests = [];

    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'id'    => 'ses_test123',
            'title' => 'Craft Skill chat',
        ], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode([
            'info'  => ['role' => 'assistant'],
            'parts' => [
                ['type' => 'step-start'],
                ['type' => 'text', 'text' => 'Hello'],
                ['type' => 'text', 'text' => ' world'],
                ['type' => 'step-finish', 'reason' => 'stop'],
            ],
        ], JSON_THROW_ON_ERROR)),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$requests): callable {
        return function (Request $request, array $options) use ($handler, &$requests) {
            $requests[] = $request;
            return $handler($request, $options);
        };
    });

    $client = new Client(['handler' => $stack]);
    $driver = new OpenCodeDriver(
        baseUrl: 'http://127.0.0.1:4096',
        client:  $client,
    );

    $events = [];
    $result = $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'Hi']],
        tools: [],
        systemPrompt: 'Act as a helpful assistant.',
        onEvent: function (array $event) use (&$events) {
            $events[] = $event;
        },
    );

    expect($requests)->toHaveCount(2);
    expect((string) $requests[0]->getUri())->toBe('http://127.0.0.1:4096/session');
    expect((string) $requests[1]->getUri())->toBe('http://127.0.0.1:4096/session/ses_test123/message');

    // Session create body must serialize as {} (empty JSON object) rather than
    // a title-bearing payload — that's what lets OpenCode auto-generate a
    // summary title on the first message.
    expect((string) $requests[0]->getBody())->toBe('{}');

    $messageBody = json_decode((string) $requests[1]->getBody(), true);
    expect($messageBody['system'])->toBe('Act as a helpful assistant.');
    expect($messageBody['parts'][0]['type'])->toBe('text');
    expect($messageBody['parts'][0]['text'])->toBe('Hi');
    expect($messageBody)->not->toHaveKey('title');

    expect($events)->toHaveCount(2);
    expect($events[0])->toBe(['type' => 'text', 'content' => 'Hello']);
    expect($events[1])->toBe(['type' => 'text', 'content' => ' world']);

    expect($result['role'])->toBe('assistant');
    expect($result['content'])->toBe('Hello world');
    expect($result)->not->toHaveKey('toolCalls');
});

test('OpenCodeDriver appends directory query param to every request when configured', function () {
    /** @var array<int, Request> $requests */
    $requests = [];

    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 'ses_dir'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['parts' => []], JSON_THROW_ON_ERROR)),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$requests): callable {
        return function (Request $request, array $options) use ($handler, &$requests) {
            $requests[] = $request;
            return $handler($request, $options);
        };
    });

    $driver = new OpenCodeDriver(
        baseUrl:   'http://127.0.0.1:4096',
        directory: '/Users/me/Sites/craft project',
        client:    new Client(['handler' => $stack]),
    );

    $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'Hi']],
        tools: [],
        systemPrompt: '',
        onEvent: fn () => null,
    );

    $encoded = rawurlencode('/Users/me/Sites/craft project');
    expect((string) $requests[0]->getUri())->toBe("http://127.0.0.1:4096/session?directory={$encoded}");
    expect((string) $requests[1]->getUri())->toBe("http://127.0.0.1:4096/session/ses_dir/message?directory={$encoded}");
});

// ─── Heartbeat / keep-alive during long upstream silences ───────────

test('HttpHeartbeat emits once per interval and not faster', function () {
    $time = 1000.0;
    $clock = function () use (&$time): float { return $time; };

    $heartbeat = new HttpHeartbeat(intervalSeconds: 5.0, clock: $clock);

    $emitted = 0;
    $onEvent = function (array $e) use (&$emitted) {
        expect($e)->toBe(['type' => 'heartbeat']);
        $emitted++;
    };

    // Less than interval — nothing emitted.
    $time = 1004.9;
    $heartbeat->tick($onEvent);
    expect($emitted)->toBe(0);

    // At interval — emits once.
    $time = 1005.0;
    $heartbeat->tick($onEvent);
    expect($emitted)->toBe(1);

    // Immediately after — still throttled.
    $time = 1005.1;
    $heartbeat->tick($onEvent);
    expect($emitted)->toBe(1);

    // Another full interval — emits again.
    $time = 1010.0;
    $heartbeat->tick($onEvent);
    expect($emitted)->toBe(2);
});

test('OpenCodeDriver registers a progress callback so SSE stays alive during blocking POSTs', function () {
    /** @var array<int, array<string, mixed>> $optionsLog */
    $optionsLog = [];

    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 'ses_hb'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode([
            'parts' => [['type' => 'text', 'text' => 'ok']],
        ], JSON_THROW_ON_ERROR)),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$optionsLog): callable {
        return function (Request $request, array $options) use ($handler, &$optionsLog) {
            $optionsLog[] = $options;
            return $handler($request, $options);
        };
    });

    $driver = new OpenCodeDriver(
        baseUrl: 'http://127.0.0.1:4096',
        client:  new Client(['handler' => $stack]),
    );

    $events = [];
    $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'hello']],
        tools: [],
        systemPrompt: '',
        onEvent: function (array $e) use (&$events) {
            $events[] = $e;
        },
    );

    // Both the session-create and the message POST must carry a progress
    // callback so long idle waits on either call emit heartbeats.
    expect($optionsLog)->toHaveCount(2);
    foreach ($optionsLog as $opts) {
        expect($opts)->toHaveKey('progress');
        expect($opts['progress'])->toBeCallable();
    }

    // Drive the progress callback manually — simulates cURL firing it during
    // a long blocking POST. Two rapid fires should not double-emit; a fire
    // after >5s of simulated time should emit.
    $progress = $optionsLog[1]['progress'];
    $heartbeatEvents = [];
    $collector = function (array $e) use (&$heartbeatEvents) {
        $heartbeatEvents[] = $e;
    };

    // Guzzle passes four ints; our progress is defined to accept them.
    $progress(100, 0, 0, 0);
    $progress(100, 0, 0, 0);
    expect($heartbeatEvents)->toBeEmpty();  // throttled — still within interval
});

test('AnthropicDriver registers a progress callback on its streaming request', function () {
    /** @var array<int, array<string, mixed>> $optionsLog */
    $optionsLog = [];

    // Minimal valid Anthropic SSE body — one text delta, then a stop.
    $body = implode('', [
        "event: content_block_start\n",
        'data: ' . json_encode(['type' => 'content_block_start', 'index' => 0, 'content_block' => ['type' => 'text', 'text' => '']]) . "\n\n",
        "event: content_block_delta\n",
        'data: ' . json_encode(['type' => 'content_block_delta', 'index' => 0, 'delta' => ['type' => 'text_delta', 'text' => 'hi']]) . "\n\n",
        "event: content_block_stop\n",
        'data: ' . json_encode(['type' => 'content_block_stop', 'index' => 0]) . "\n\n",
    ]);

    $mock  = new MockHandler([new Response(200, [], $body)]);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$optionsLog): callable {
        return function (Request $request, array $options) use ($handler, &$optionsLog) {
            $optionsLog[] = $options;
            return $handler($request, $options);
        };
    });

    $driver = new AnthropicDriver(
        apiKey: 'test',
        client: new Client(['handler' => $stack]),
    );

    $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'hi']],
        tools: [],
        systemPrompt: 'system',
        onEvent: fn () => null,
    );

    expect($optionsLog)->toHaveCount(1);
    expect($optionsLog[0])->toHaveKey('progress');
    expect($optionsLog[0]['progress'])->toBeCallable();
});

test('OpenAiDriver registers a progress callback on its streaming request', function () {
    /** @var array<int, array<string, mixed>> $optionsLog */
    $optionsLog = [];

    $body = implode('', [
        'data: ' . json_encode(['choices' => [['delta' => ['content' => 'hi'], 'finish_reason' => null]]]) . "\n",
        "data: [DONE]\n",
    ]);

    $mock  = new MockHandler([new Response(200, [], $body)]);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$optionsLog): callable {
        return function (Request $request, array $options) use ($handler, &$optionsLog) {
            $optionsLog[] = $options;
            return $handler($request, $options);
        };
    });

    $driver = new OpenAiDriver(
        apiKey: 'test',
        client: new Client(['handler' => $stack]),
    );

    $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'hi']],
        tools: [],
        systemPrompt: 'system',
        onEvent: fn () => null,
    );

    expect($optionsLog)->toHaveCount(1);
    expect($optionsLog[0])->toHaveKey('progress');
    expect($optionsLog[0]['progress'])->toBeCallable();
});

test('OpenCodeDriver sends basic auth when a password is configured', function () {
    /** @var array<int, Request> $requests */
    $requests = [];

    $mock = new MockHandler([
        new Response(200, [], json_encode(['id' => 'ses_auth'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['parts' => []], JSON_THROW_ON_ERROR)),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$requests): callable {
        return function (Request $request, array $options) use ($handler, &$requests) {
            $requests[] = $request;
            return $handler($request, $options);
        };
    });

    $driver = new OpenCodeDriver(
        baseUrl:  'http://127.0.0.1:4096',
        password: 'sekret',
        client:   new Client(['handler' => $stack]),
    );

    $driver->streamChat(
        messages: [['role' => 'user', 'content' => 'Hi']],
        tools: [],
        systemPrompt: '',
        onEvent: fn () => null,
    );

    $expected = 'Basic ' . base64_encode('opencode:sekret');
    expect($requests[0]->getHeaderLine('Authorization'))->toBe($expected);
    expect($requests[1]->getHeaderLine('Authorization'))->toBe($expected);
});
