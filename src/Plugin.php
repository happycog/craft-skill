<?php

namespace happycog\craftmcp;

use Craft;
use happycog\craftmcp\attributes\BindToContainer;
use happycog\craftmcp\attributes\RegisterListener;
use happycog\craftmcp\base\Container as Psr11ContainerProxy;
use happycog\craftmcp\base\Plugin as BasePlugin;
use happycog\craftmcp\transports\StreamableHttpServerTransport;
use happycog\craftmcp\transports\HttpServerTransport;
use happycog\craftmcp\session\CraftSessionHandler;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Server;
use yii\di\Container;

class Plugin extends BasePlugin
{
    #[BindToContainer(singleton: true)]
    protected function registerMcpServer(Container $container): Server
    {
        $capabilities = ServerCapabilities::make(
            tools: true,
            resources: false,
            prompts: false,
        );

        $sessionHandler = $container->get(CraftSessionHandler::class);

        $server = Server::make()
            ->withServerInfo('Craft CMS MCP Server', '1.0.0')
            ->withCapabilities($capabilities)
            ->withSessionHandler($sessionHandler)
            ->withContainer(Craft::$container->get(Psr11ContainerProxy::class))
            ->build();

        $basePath = Craft::getAlias('@happycog/craftmcp');
        throw_unless($basePath !== false, 'Unable to resolve plugin alias path');

        $server->discover(
            basePath: $basePath,
            scanDirs: ['tools'],
            force: Craft::$app->getConfig()->getGeneral()->devMode,
        );

        return $server;
    }

    #[BindToContainer(singleton: true)]
    protected function registerHttpTransport(Container $container): StreamableHttpServerTransport
    {
        $server = $container->get(Server::class);
        $sessionHandler = $container->get(CraftSessionHandler::class);
        $transport = new StreamableHttpServerTransport($sessionHandler);

        // Bind the protocol to the transport but don't start listening
        $protocol = $server->getProtocol();
        $protocol->bindTransport($transport);
        
        return $transport;
    }

    #[BindToContainer(singleton: true)]
    protected function registerSseTransport(Container $container): HttpServerTransport
    {
        $server = $container->get(Server::class);
        $sessionManager = $server->getSessionManager();
        $transport = new HttpServerTransport($sessionManager);

        // Listen to the transport
        $server->listen($transport, false);
        
        return $transport;
    }

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        // The older SSE transport routes
        $event->rules['GET sse'] = 'skills/sse-transport/sse';
        $event->rules['POST message'] = 'skills/sse-transport/message';

        // The newer streamable streaming HTTP transport
        $event->rules['GET mcp'] = 'skills/streamable-transport/listen';
        $event->rules['POST mcp'] = 'skills/streamable-transport/message';
        $event->rules['DELETE mcp'] = 'skills/streamable-transport/disconnect';

        // API routes for Skills
        $apiPrefix = $this->getSettings()->apiPrefix ?? 'api';
        
        // Section routes
        $event->rules['POST ' . $apiPrefix . '/sections'] = 'skills/sections/create';
        $event->rules['GET ' . $apiPrefix . '/sections'] = 'skills/sections/list';
        $event->rules['PUT ' . $apiPrefix . '/sections/<id>'] = 'skills/sections/update';
        $event->rules['DELETE ' . $apiPrefix . '/sections/<id>'] = 'skills/sections/delete';
        
        // Entry Type routes
        $event->rules['POST ' . $apiPrefix . '/entry-types'] = 'skills/entry-types/create';
        $event->rules['GET ' . $apiPrefix . '/entry-types'] = 'skills/entry-types/list';
        $event->rules['PUT ' . $apiPrefix . '/entry-types/<id>'] = 'skills/entry-types/update';
        $event->rules['DELETE ' . $apiPrefix . '/entry-types/<id>'] = 'skills/entry-types/delete';
        
        // Field routes
        $event->rules['POST ' . $apiPrefix . '/fields'] = 'skills/fields/create';
        $event->rules['GET ' . $apiPrefix . '/fields'] = 'skills/fields/list';
        $event->rules['GET ' . $apiPrefix . '/fields/types'] = 'skills/fields/types';
        $event->rules['PUT ' . $apiPrefix . '/fields/<id>'] = 'skills/fields/update';
        $event->rules['DELETE ' . $apiPrefix . '/fields/<id>'] = 'skills/fields/delete';
        
        // Entry routes
        $event->rules['POST ' . $apiPrefix . '/entries'] = 'skills/entries/create';
        $event->rules['GET ' . $apiPrefix . '/entries/search'] = 'skills/entries/search';
        $event->rules['GET ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/get';
        $event->rules['PUT ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/update';
        $event->rules['DELETE ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/delete';
        
        // Draft routes
        $event->rules['POST ' . $apiPrefix . '/drafts'] = 'skills/drafts/create';
        $event->rules['PUT ' . $apiPrefix . '/drafts/<id>'] = 'skills/drafts/update';
        $event->rules['POST ' . $apiPrefix . '/drafts/<id>/apply'] = 'skills/drafts/apply';
        
        // Field Layout routes
        $event->rules['POST ' . $apiPrefix . '/field-layouts'] = 'skills/field-layouts/create';
        $event->rules['GET ' . $apiPrefix . '/field-layouts'] = 'skills/field-layouts/get';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/tabs'] = 'skills/field-layouts/add-tab';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/fields'] = 'skills/field-layouts/add-field';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/ui-elements'] = 'skills/field-layouts/add-ui-element';
        $event->rules['DELETE ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'skills/field-layouts/remove-element';
        $event->rules['PUT ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'skills/field-layouts/move-element';
        
        // Site routes
        $event->rules['GET ' . $apiPrefix . '/sites'] = 'skills/sites/list';
        
        // Health check route
        $event->rules['GET ' . $apiPrefix . '/health'] = 'skills/health/index';
    }
}
