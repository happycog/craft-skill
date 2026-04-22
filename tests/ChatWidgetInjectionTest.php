<?php

use craft\events\TemplateEvent;
use craft\web\View;
use happycog\craftmcp\Plugin;
use markhuot\craftpest\factories\Entry;

test('chat widget injection skips guests', function () {
    Craft::$app->getUser()->logout(false);

    $event = new TemplateEvent([
        'template' => 'index',
        'variables' => [],
        'templateMode' => View::TEMPLATE_MODE_SITE,
        'output' => '<html><body><main>Test</main></body></html>',
    ]);

    /** @var Plugin $plugin */
    $plugin = Craft::$app->getPlugins()->getPlugin('skills');

    expect($plugin)->not->toBeNull();

    $method = new ReflectionMethod($plugin, 'injectChatUi');
    $method->setAccessible(true);
    $method->invoke($plugin, $event);

    expect($event->output)->not->toContain('<craft-skill-chat');
});

test('chat widget injection skips users without control panel access', function () {
    $user = $this->createMock(\craft\elements\User::class);
    $user->method('can')->with('accessCp')->willReturn(false);
    Craft::$app->getUser()->setIdentity($user);

    $event = new TemplateEvent([
        'template' => 'index',
        'variables' => [],
        'templateMode' => View::TEMPLATE_MODE_SITE,
        'output' => '<html><body><main>Test</main></body></html>',
    ]);

    /** @var Plugin $plugin */
    $plugin = Craft::$app->getPlugins()->getPlugin('skills');

    expect($plugin)->not->toBeNull();

    $method = new ReflectionMethod($plugin, 'injectChatUi');
    $method->setAccessible(true);
    $method->invoke($plugin, $event);

    expect($event->output)->not->toContain('<craft-skill-chat');
});

test('chat widget injection includes serialized page context', function () {
    $user = createTestUser(admin: true);
    Craft::$app->getUser()->setIdentity($user);

    $entry = Entry::factory()
        ->section('news')
        ->title('Injected Entry')
        ->slug('injected-entry')
        ->create();

    Craft::$app->requestedRoute = 'templates/render';
    Craft::$app->getUrlManager()->setMatchedElement($entry);
    Craft::$app->getUrlManager()->setRouteParams([
        'entryHandle' => 'news',
        'preview' => false,
    ], merge: false);

    $event = new TemplateEvent([
        'template' => 'news/_entry',
        'variables' => [],
        'templateMode' => View::TEMPLATE_MODE_SITE,
        'output' => '<html><body><main>Test</main></body></html>',
    ]);

    /** @var Plugin $plugin */
    $plugin = Craft::$app->getPlugins()->getPlugin('skills');

    expect($plugin)->not->toBeNull();

    $method = new ReflectionMethod($plugin, 'injectChatUi');
    $method->setAccessible(true);
    $method->invoke($plugin, $event);

    expect($event->output)->toContain('<craft-skill-chat')
        ->toContain('data-page-context=')
        ->toContain('&quot;surface&quot;:&quot;site&quot;')
        ->toContain('&quot;elementId&quot;:' . $entry->id)
        ->toContain('&quot;elementTitle&quot;:&quot;Injected Entry&quot;')
        ->toContain('&quot;elementSlug&quot;:&quot;injected-entry&quot;')
        ->toContain('&quot;elementUri&quot;:&quot;news/injected-entry&quot;')
        ->toContain('&quot;requestedRoute&quot;:&quot;templates/render&quot;')
        ->toContain('&quot;routeParams&quot;:{&quot;entryHandle&quot;:&quot;news&quot;,&quot;preview&quot;:false}');
});

test('chat widget injection includes draftId when route params indicate a draft', function () {
    $user = createTestUser(admin: true);
    Craft::$app->getUser()->setIdentity($user);

    $entry = Entry::factory()
        ->section('news')
        ->title('Draft Context Entry')
        ->slug('draft-context-entry')
        ->create();

    Craft::$app->requestedRoute = 'entries/edit-entry';
    Craft::$app->getUrlManager()->setMatchedElement($entry);
    Craft::$app->getUrlManager()->setRouteParams([
        'siteId' => $entry->siteId,
        'draftId' => 456,
    ], merge: false);

    $event = new TemplateEvent([
        'template' => 'news/_entry',
        'variables' => [],
        'templateMode' => View::TEMPLATE_MODE_SITE,
        'output' => '<html><body><main>Test</main></body></html>',
    ]);

    /** @var Plugin $plugin */
    $plugin = Craft::$app->getPlugins()->getPlugin('skills');

    expect($plugin)->not->toBeNull();

    $method = new ReflectionMethod($plugin, 'injectChatUi');
    $method->setAccessible(true);
    $method->invoke($plugin, $event);

    expect($event->output)->toContain('&quot;draftId&quot;:456')
        ->toContain('&quot;routeParams&quot;:{&quot;siteId&quot;:' . $entry->siteId . ',&quot;draftId&quot;:456}');
});
