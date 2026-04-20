<?php

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use happycog\craftmcp\mcp\McpServerFactory;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;

/**
 * Drives the MCP server through its StreamableHttpTransport — the same path
 * our HTTP controller exercises — to verify the JSON-RPC round-trip end-to-end
 * over all registered tools.
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

test('tools/list advertises every registered Craft skill tool', function () {
    $server = Craft::$container->get(McpServerFactory::class)->create();
    $sessionId = mcpInitialize($server);

    $names = [];
    $cursor = null;
    $requestId = 2;

    do {
        $params = (object) [];
        if ($cursor !== null) {
            $params = ['cursor' => $cursor];
        }

        $response = mcpSend($server, [
            'jsonrpc' => '2.0',
            'id' => $requestId++,
            'method' => 'tools/list',
            'params' => $params,
        ], $sessionId);

        expect($response->getStatusCode())->toBe(200);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        expect($payload)->toHaveKey('result');
        expect($payload['result'])->toHaveKey('tools');

        foreach ($payload['result']['tools'] as $tool) {
            $names[] = $tool['name'];
        }

        $cursor = $payload['result']['nextCursor'] ?? null;
    } while ($cursor !== null);

    foreach (\happycog\craftmcp\base\CommandMap::MAP as $class) {
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

    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('result');
    expect($payload['result'])->toHaveKey('content');

    $text = collect($payload['result']['content'])
        ->where('type', 'text')
        ->pluck('text')
        ->implode("\n");

    $decoded = json_decode($text, true);
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('status');
    expect($decoded['status'])->toBe('ok');
    expect($decoded)->toHaveKey('plugin');
    expect($decoded)->toHaveKey('craft');
    expect($decoded)->toHaveKey('site');
});
