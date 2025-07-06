<?php

use markhuot\craftmcp\transports\StreamableHttpServerTransport;
use PhpMcp\Server\Server;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Schema\ListToolsCapability;
use craft\web\Request;
use craft\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

beforeEach(function () {
    // Create a test transport (no need for full server in basic tests)
    $this->transport = new StreamableHttpServerTransport();
});

test('transport can be instantiated', function () {
    expect($this->transport)->toBeInstanceOf(StreamableHttpServerTransport::class);
});

test('transport starts without error', function () {
    $this->transport->listen();
    expect($this->transport->isListening())->toBeTrue();
});

test('transport closes without error', function () {
    $this->transport->close();
    expect($this->transport->getSessions())->toBeArray()->toBeEmpty();
});

test('handlePost requires JSON content type', function () {
    $request = new Request();
    $response = new Response();

    expect(fn() => $this->transport->handlePost($request, $response))
        ->toThrow(BadRequestHttpException::class, 'Content-Type must be application/json');
});

test('handlePost generates session ID when none provided', function () {
    $request = new Request();
    $request->getHeaders()->set('content-type', 'application/json');

    $request->setBodyParams(['method' => 'initialize', 'id' => 1]);
    
    $response = new Response();

    $result = $this->transport->handlePost($request, $response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($this->transport->getCurrentSessionId())->toBeString()->toStartWith('craft_mcp_');
    expect($this->transport->getSessions())->toHaveCount(1);
});

test('handlePost uses provided session ID for non-initialize requests', function () {
    // First, create a session with initialize
    $initRequest = new Request();
    $initRequest->getHeaders()->set('content-type', 'application/json');
    $initResponse = new Response();
    $initRequest->setBodyParams(['method' => 'initialize', 'id' => 1]);
    
    $this->transport->handlePost($initRequest, $initResponse);
    $sessionId = $this->transport->getCurrentSessionId();
    
    // Now test a non-initialize request with the session ID
    $request = new Request();
    $request->getHeaders()->set('content-type', 'application/json');
    $response = new Response();
    
    $request->setQueryParams(['sessionId' => $sessionId]);
    $request->setBodyParams(['method' => 'tools/list', 'id' => 2]);

    $result = $this->transport->handlePost($request, $response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($this->transport->getCurrentSessionId())->toBe($sessionId);
    expect($this->transport->getSessions())->toHaveKey($sessionId);
});

test('handlePost requires method in request body', function () {
    $request = new Request();
    $request->getHeaders()->set('content-type', 'application/json');
    $response = new Response();
    
    $request->setBodyParams([]);

    expect(fn() => $this->transport->handlePost($request, $response))
        ->toThrow(BadRequestHttpException::class, 'Invalid JSON-RPC request');
});

test('handleGet requires valid session ID', function () {
    $request = Craft::createObject([
        'class' => Request::class,
    ]);
    $request->setQueryParams(['sessionId' => 'nonexistent-session']);
    $response = new Response();

    expect(fn() => $this->transport->handleGet($request, $response))
        ->toThrow(NotFoundHttpException::class, 'Session not found');
});

test('handleGet sets SSE headers correctly', function () {
    // First create a session using reflection to access the protected method
    $sessionId = 'test-session-sse';
    $reflection = new ReflectionClass($this->transport);
    $method = $reflection->getMethod('initializeSession');
    $method->setAccessible(true);
    $method->invoke($this->transport, $sessionId);

    $request = Craft::createObject([
        'class' => Request::class,
    ]);
    $request->setQueryParams(['sessionId' => $sessionId]);
    $response = new Response();

    $result = $this->transport->handleGet($request, $response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($response->format)->toBe(Response::FORMAT_RAW);
    expect($response->headers->get('Content-Type'))->toBe('text/event-stream');
    expect($response->headers->get('Cache-Control'))->toBe('no-cache, must-revalidate');
    expect($response->headers->get('Connection'))->toBe('keep-alive');
});

test('handleDelete removes session', function () {
    // First create a session using reflection
    $sessionId = 'test-session-delete';
    $reflection = new ReflectionClass($this->transport);
    $method = $reflection->getMethod('initializeSession');
    $method->setAccessible(true);
    $method->invoke($this->transport, $sessionId);

    $request = Craft::createObject([
        'class' => Request::class,
    ]);
    $request->setQueryParams(['sessionId' => $sessionId]);
    $response = new Response();

    $result = $this->transport->handleDelete($request, $response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($response->data)->toBe(['success' => true]);
    expect($this->transport->getSessions())->not->toHaveKey($sessionId);
});

test('handleDelete succeeds even with nonexistent session', function () {
    $request = Craft::createObject([
        'class' => Request::class,
    ]);
    $request->setQueryParams(['sessionId' => 'nonexistent-session']);
    $response = new Response();

    $result = $this->transport->handleDelete($request, $response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($response->data)->toBe(['success' => true]);
});

test('cleanupSessions removes old sessions', function () {
    $oldSessionId = 'old-session';
    $newSessionId = 'new-session';
    
    // Use reflection to access sessions property directly
    $reflection = new ReflectionClass($this->transport);
    $property = $reflection->getProperty('sessions');
    $property->setAccessible(true);
    
    // Create sessions directly
    $sessions = [
        $oldSessionId => [
            'id' => $oldSessionId,
            'created_at' => time() - 7200, // 2 hours ago
            'messages' => [],
        ],
        $newSessionId => [
            'id' => $newSessionId,
            'created_at' => time(),
            'messages' => [],
        ]
    ];
    $property->setValue($this->transport, $sessions);

    // Cleanup sessions older than 1 hour (3600 seconds)
    $this->transport->cleanupSessions(3600);

    expect($this->transport->getSessions())->not->toHaveKey($oldSessionId);
    expect($this->transport->getSessions())->toHaveKey($newSessionId);
});

test('session handler generates unique IDs', function () {
    $sessionHandler = new \markhuot\craftmcp\session\CraftSessionHandler();
    
    $id1 = $sessionHandler->generateSessionId();
    $id2 = $sessionHandler->generateSessionId();

    expect($id1)->toBeString()->toStartWith('craft_mcp_');
    expect($id2)->toBeString()->toStartWith('craft_mcp_');
    expect($id1)->not->toBe($id2);
});