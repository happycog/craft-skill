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
        $event->rules['GET sse'] = 'mcp/sse-transport/sse';
        $event->rules['POST message'] = 'mcp/sse-transport/message';

        // The newer streamable streaming HTTP transport
        $event->rules['GET mcp'] = 'mcp/streamable-transport/listen';
        $event->rules['POST mcp'] = 'mcp/streamable-transport/message';
        $event->rules['DELETE mcp'] = 'mcp/streamable-transport/disconnect';

        // API routes for Skills
        $apiPrefix = $this->getSettings()->apiPrefix ?? 'api';
        
        // Section routes
        $event->rules['POST ' . $apiPrefix . '/sections'] = 'mcp/sections/create';
        $event->rules['GET ' . $apiPrefix . '/sections'] = 'mcp/sections/list';
        $event->rules['PUT ' . $apiPrefix . '/sections/<id>'] = 'mcp/sections/update';
        $event->rules['DELETE ' . $apiPrefix . '/sections/<id>'] = 'mcp/sections/delete';
        
        // Entry Type routes
        $event->rules['POST ' . $apiPrefix . '/entry-types'] = 'mcp/entry-types/create';
        $event->rules['GET ' . $apiPrefix . '/entry-types'] = 'mcp/entry-types/list';
        $event->rules['PUT ' . $apiPrefix . '/entry-types/<id>'] = 'mcp/entry-types/update';
        $event->rules['DELETE ' . $apiPrefix . '/entry-types/<id>'] = 'mcp/entry-types/delete';
        
        // Field routes
        $event->rules['POST ' . $apiPrefix . '/fields'] = 'mcp/fields/create';
        $event->rules['GET ' . $apiPrefix . '/fields'] = 'mcp/fields/list';
        $event->rules['GET ' . $apiPrefix . '/fields/types'] = 'mcp/fields/types';
        $event->rules['PUT ' . $apiPrefix . '/fields/<id>'] = 'mcp/fields/update';
        $event->rules['DELETE ' . $apiPrefix . '/fields/<id>'] = 'mcp/fields/delete';
        
        // Entry routes
        $event->rules['POST ' . $apiPrefix . '/entries'] = 'mcp/entries/create';
        $event->rules['GET ' . $apiPrefix . '/entries/search'] = 'mcp/entries/search';
        $event->rules['GET ' . $apiPrefix . '/entries/<id>'] = 'mcp/entries/get';
        $event->rules['PUT ' . $apiPrefix . '/entries/<id>'] = 'mcp/entries/update';
        $event->rules['DELETE ' . $apiPrefix . '/entries/<id>'] = 'mcp/entries/delete';
        
        // Draft routes
        $event->rules['POST ' . $apiPrefix . '/drafts'] = 'mcp/drafts/create';
        $event->rules['PUT ' . $apiPrefix . '/drafts/<id>'] = 'mcp/drafts/update';
        $event->rules['POST ' . $apiPrefix . '/drafts/<id>/apply'] = 'mcp/drafts/apply';
        
        // Field Layout routes
        $event->rules['POST ' . $apiPrefix . '/field-layouts'] = 'mcp/field-layouts/create';
        $event->rules['GET ' . $apiPrefix . '/field-layouts'] = 'mcp/field-layouts/get';
        $event->rules['PUT ' . $apiPrefix . '/field-layouts/<id>'] = 'mcp/field-layouts/update';
        
        // Site routes
        $event->rules['GET ' . $apiPrefix . '/sites'] = 'mcp/sites/list';
    }
}
