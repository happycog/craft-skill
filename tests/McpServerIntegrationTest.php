<?php

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use happycog\craftmcp\mcp\McpServerFactory;
use happycog\craftmcp\tools\CreateEntry;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;

/**
 * Drives the MCP server through its StreamableHttpTransport — the same path
 * our HTTP controller exercises — to verify the JSON-RPC round-trip end-to-end
 * over all registered tools.
 */

/**
 * @param array<string, mixed> $jsonRpc
 */
function mcpSend(Server $server, array $jsonRpc, ?string $sessionId = null): ResponseInterface
{
    $body = json_encode($jsonRpc, JSON_THROW_ON_ERROR);
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/event-stream',
    ];
    if ($sessionId !== null) {
        $headers['Mcp-Session-Id'] = $sessionId;
    }

    $request = new ServerRequest('POST', '/mcp', $headers, $body);
    $psr17 = new HttpFactory();

    $transport = new StreamableHttpTransport(
        request: $request,
        responseFactory: $psr17,
        streamFactory: $psr17,
    );

    return $server->run($transport);
}

function mcpInitialize(Server $server): string
{
    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'pest-test', 'version' => '1.0'],
        ],
    ]);

    expect($response->getStatusCode())->toBe(200);
    expect($response->hasHeader('Mcp-Session-Id'))->toBeTrue();

    // MCP requires an `initialized` notification after the handshake before
    // the session can service additional requests.
    mcpSend($server, [
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized',
    ], $response->getHeaderLine('Mcp-Session-Id'));

    return $response->getHeaderLine('Mcp-Session-Id');
}

test('initialize handshake returns server capabilities and a session id', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);
    expect($sessionId)->not->toBeEmpty();
});

test('MCP sessions survive beyond the SDK default 1-hour TTL', function () {
    // OpenCode keeps a single MCP client connection open for the life of its
    // `opencode serve` process and only hits us when the user sends a chat
    // message. With the SDK's stock 3600s TTL, any idle window over an hour
    // made the next tool call fail with "Session not found or has expired."
    // McpServerFactory bumps the TTL to 30 days to match real-world usage.
    $factory = Craft::$container->get(McpServerFactory::class);
    $server  = $factory->create();
    $sessionId = mcpInitialize($server);

    $sessionFile = Craft::$app->getPath()->getRuntimePath()
        . DIRECTORY_SEPARATOR . 'craft-skills-mcp'
        . DIRECTORY_SEPARATOR . $sessionId;
    expect(is_file($sessionFile))->toBeTrue();

    // Backdate to 2 hours — past the SDK default, well inside our 30-day TTL.
    touch($sessionFile, time() - 7200);

    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id'      => 99,
        'method'  => 'tools/list',
        'params'  => (object) [],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result?: array{tools: array<int, array{name: string}>}, error?: array{message: string}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($payload)->toHaveKey('result');
    expect($payload)->not->toHaveKey('error');
});

test('session persists across separate server instances', function () {
    $factory = Craft::$container->get(McpServerFactory::class);

    $firstServer = $factory->create();
    $sessionId = mcpInitialize($firstServer);

    $secondServer = $factory->create();
    $response = mcpSend($secondServer, [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
        'params' => (object) [],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result: array{tools: array<int, array{name: string}>}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result'])->toHaveKey('tools');
    expect($payload['result']['tools'])->not->toBeEmpty();
});

test('tools/list advertises every registered Craft skill tool', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $names = [];
    $cursor = null;
    $requestId = 2;

    do {
        $params = (object) [];
        if ($cursor !== null) {
            /** @var array{cursor: string} $params */
            $params = ['cursor' => $cursor];
        }

        $response = mcpSend($server, [
            'jsonrpc' => '2.0',
            'id' => $requestId++,
            'method' => 'tools/list',
            'params' => $params,
        ], $sessionId);

        expect($response->getStatusCode())->toBe(200);

        /** @var array{result: array{tools: array<int, array{name: string}>, nextCursor?: string}} $payload */
        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        expect($payload)->toHaveKey('result');
        expect($payload['result'])->toHaveKey('tools');

        foreach ($payload['result']['tools'] as $tool) {
            $names[] = $tool['name'];
        }

        $cursor = $payload['result']['nextCursor'] ?? null;
    } while ($cursor !== null);

    foreach (\happycog\craftmcp\base\CommandMap::all() as $class) {
        $expectedName = (new \ReflectionClass($class))->getShortName();
        expect($names)->toContain($expectedName);
    }
});

test('tools/call invokes a tool and returns its result', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'GetHealth',
            'arguments' => (object) [],
        ],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result: array{content: array<int, array{type: string, text?: string}>, structuredContent: array<string, mixed>}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result'])->toHaveKey('content');
    expect($payload['result'])->toHaveKey('structuredContent');

    $textParts = [];

    foreach ($payload['result']['content'] as $content) {
        if ($content['type'] === 'text' && is_string($content['text'] ?? null)) {
            $textParts[] = $content['text'];
        }
    }

    /** @var array<string, mixed> $decoded */
    $decoded = $payload['result']['structuredContent'];

    expect($decoded)->toHaveKey('status');
    expect($decoded['status'])->toBe('ok');
    expect($decoded)->toHaveKey('plugin');
    expect($decoded)->toHaveKey('craft');
    expect($decoded)->toHaveKey('site');
});

test('tools/call rejects list-shaped attributeAndFieldData as invalid params', function () {
    $section = Craft::$app->getEntries()->getSectionByHandle('news');
    expect($section)->not->toBeNull();
    if ($section === null) {
        throw new RuntimeException('Expected news section to exist.');
    }

    $entryTypeId = $section->getEntryTypes()[0]->id;

    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id' => 6,
        'method' => 'tools/call',
        'params' => [
            'name' => 'CreateDraft',
            'arguments' => [
                'sectionId' => $section->id,
                'entryTypeId' => $entryTypeId,
                'attributeAndFieldData' => [
                    ['title' => 'Bad Shape'],
                ],
            ],
        ],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{error: array{code: int, message: string, data?: array{validation_errors?: array<int, array{message?: string}>}}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('error');
    expect($payload['error']['code'])->toBe(-32602);
    expect($payload['error']['message'])->toContain('Invalid parameters for tool');
    expect(json_encode($payload['error']['data'] ?? [], JSON_THROW_ON_ERROR))->toContain('attributeAndFieldData');
});

test('tools/call returns visible tool errors and includes debug details in dev mode', function () {
    $createEntry = Craft::$container->get(CreateEntry::class);
    $section = Craft::$app->getEntries()->getSectionByHandle('news');
    expect($section)->not->toBeNull();
    if ($section === null) {
        throw new RuntimeException('Expected news section to exist.');
    }

    $entryTypeId = $section->getEntryTypes()[0]->id;
    $sectionId = $section->id;
    if ($sectionId === null || $entryTypeId === null) {
        throw new RuntimeException('Expected news section and entry type IDs to exist.');
    }

    $createEntry->__invoke(
        sectionId: $sectionId,
        entryTypeId: $entryTypeId,
        attributeAndFieldData: ['title' => 'Delete Me'],
    );

    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalDevMode = $generalConfig->devMode;
    $generalConfig->devMode = true;

    try {
        $server = Craft::$container->get(McpServerFactory::class)->create();
        $sessionId = mcpInitialize($server);

        $response = mcpSend($server, [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => [
                'name' => 'DeleteSection',
                'arguments' => [
                    'sectionId' => $sectionId,
                ],
            ],
        ], $sessionId);
    } finally {
        $generalConfig->devMode = $originalDevMode;
    }

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result: array{isError: bool, content: array<int, array{type: string, text?: string}>, structuredContent: array<string, mixed>}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result']['isError'])->toBeTrue();
    expect($payload['result'])->toHaveKey('structuredContent');
    expect($payload['result']['structuredContent'])->toHaveKey('tool', 'DeleteSection');
    expect($payload['result']['structuredContent'])->toHaveKey('exception', RuntimeException::class);
    expect($payload['result']['structuredContent'])->toHaveKey('debug');

    $text = implode("\n", array_map(
        static fn (array $content): string => is_string($content['text'] ?? null) ? $content['text'] : '',
        $payload['result']['content'],
    ));

    expect($text)->toContain('DeleteSection failed:')
        ->toContain('Debug:')
        ->toContain('Exception: RuntimeException');
});

test('prompts/list advertises the AI widget system prompt', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id' => 4,
        'method' => 'prompts/list',
        'params' => (object) [],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result: array{prompts: array<int, array{name: string}>}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result'])->toHaveKey('prompts');

    $names = [];

    foreach ($payload['result']['prompts'] as $prompt) {
        $names[] = $prompt['name'];
    }

    expect($names)->toContain(\happycog\craftmcp\llm\LlmManager::AI_WIDGET_SYSTEM_PROMPT);
});

test('prompts/get returns the AI widget system prompt and accepts page context', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $response = mcpSend($server, [
        'jsonrpc' => '2.0',
        'id' => 5,
        'method' => 'prompts/get',
        'params' => [
            'name' => \happycog\craftmcp\llm\LlmManager::AI_WIDGET_SYSTEM_PROMPT,
            'arguments' => [
                'currentUrl' => 'https://example.test/admin/entries/homepage',
                'requestPath' => 'admin/entries/homepage',
                'requestedRoute' => 'entries/edit-entry',
                'routeParams' => [
                    'siteId' => 1,
                    'draftId' => null,
                ],
                'elementId' => 99,
                'elementType' => 'craft\\elements\\Entry',
                'elementTitle' => 'Homepage',
                'elementSlug' => 'homepage',
                'elementUri' => '__home__',
                'draftId' => 123,
                'siteId' => 1,
            ],
        ],
    ], $sessionId);

    expect($response->getStatusCode())->toBe(200);

    /** @var array{result: array{messages: array<int, array{content: array{text?: string}|mixed}>}} $payload */
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result'])->toHaveKey('messages');

    $textParts = [];

    foreach ($payload['result']['messages'] as $message) {
        $content = $message['content'];

        if (is_array($content) && is_string($content['text'] ?? null)) {
            $textParts[] = $content['text'];
        }
    }

    $text = implode("\n", $textParts);

    expect($text)->toContain('You are an AI assistant embedded in the Craft CMS chat widget.')
        ->toContain('Current page context:')
        ->toContain('- URL: https://example.test/admin/entries/homepage')
        ->toContain('- Request path: admin/entries/homepage')
        ->toContain('- Requested route: entries/edit-entry')
        ->toContain('- Route params: {"siteId":1,"draftId":null}')
        ->toContain('- Element ID: 99')
        ->toContain('- Element type: craft\\elements\\Entry')
        ->toContain('- Element title: Homepage')
        ->toContain('- Element slug: homepage')
        ->toContain('- Element URI: __home__')
        ->toContain('- Draft ID: 123')
        ->toContain('- Site ID: 1');
});
