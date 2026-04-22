<?php

use craft\events\TemplateEvent;
use craft\web\View;
use happycog\craftmcp\Plugin;
use markhuot\craftpest\factories\Entry;

test('chat widget injection includes serialized page context', function () {
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
