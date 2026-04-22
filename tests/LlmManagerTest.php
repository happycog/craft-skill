<?php

use happycog\craftmcp\llm\LlmManager;
use markhuot\craftpest\factories\Entry;

test('buildSystemPrompt includes structured page context', function () {
    $llm = Craft::$container->get(LlmManager::class);

    $prompt = $llm->buildSystemPrompt([
        'surface' => 'site',
        'currentUrl' => 'https://example.test/news/hello-world',
        'controlPanelUrl' => 'https://example.test/admin',
        'requestPath' => 'news/hello-world',
        'requestedRoute' => 'templates/render',
        'routeParams' => [
            'foo' => 'bar',
            'nested' => ['count' => 2],
        ],
        'elementId' => 123,
        'elementType' => 'craft\\elements\\Entry',
        'elementTitle' => 'Hello World',
        'elementSlug' => 'hello-world',
        'elementUri' => 'news/hello-world',
        'siteId' => 1,
    ]);

    expect($prompt)->toContain('Current page context:')
        ->toContain('OpenUrl is available only inside the chat widget.')
        ->toContain('When a tool call returns an error, read the full tool response carefully before retrying')
        ->toContain('Always keep the user on the current surface when redirecting.')
        ->toContain('- Current surface: site')
        ->toContain('- URL: https://example.test/news/hello-world')
        ->toContain('- Control panel URL: https://example.test/admin')
        ->toContain('- Request path: news/hello-world')
        ->toContain('- Requested route: templates/render')
        ->toContain('- Route params: {"foo":"bar","nested":{"count":2}}')
        ->toContain('- Element ID: 123')
        ->toContain('- Element type: craft\\elements\\Entry')
        ->toContain('- Element title: Hello World')
        ->toContain('- Element slug: hello-world')
        ->toContain('- Element URI: news/hello-world')
        ->toContain('- Site ID: 1');
});

test('pageContext includes matched element metadata', function () {
    $entry = Entry::factory()
        ->section('news')
        ->title('Context Entry')
        ->slug('context-entry')
        ->create();

    $llm = Craft::$container->get(LlmManager::class);
    $context = $llm->pageContext($entry);

    expect($context)->toMatchArray([
        'surface' => 'site',
        'elementId' => $entry->id,
        'elementType' => $entry::class,
        'elementTitle' => 'Context Entry',
        'elementSlug' => 'context-entry',
        'elementUri' => 'news/context-entry',
        'siteId' => $entry->siteId,
    ]);
});
