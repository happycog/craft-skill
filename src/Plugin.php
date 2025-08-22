<?php

namespace markhuot\craftmcp;

use Craft;
use markhuot\craftmcp\attributes\BindToContainer;
use markhuot\craftmcp\attributes\RegisterListener;
use markhuot\craftmcp\base\Plugin as BasePlugin;
use markhuot\craftmcp\transports\StreamableHttpServerTransport;
use markhuot\craftmcp\transports\HttpServerTransport;
use markhuot\craftmcp\session\CraftSessionHandler;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Server;

class Plugin extends BasePlugin
{
    #[BindToContainer(singleton: true)]
    protected function registerMcpServer($container): Server
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
            ->build();

        $server->discover(
            basePath: Craft::getAlias('@markhuot/craftmcp'),
            scanDirs: ['tools'],
            force: Craft::$app->getConfig()->getGeneral()->devMode,
        );

        return $server;
    }

    #[BindToContainer(singleton: true)]
    protected function registerHttpTransport($container): StreamableHttpServerTransport
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
    protected function registerSseTransport($container): HttpServerTransport
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
    }
}
