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
    }
}
