<?php

namespace markhuot\craftmcp;

use markhuot\craftmcp\attributes\BindToContainer;
use markhuot\craftmcp\attributes\RegisterListener;
use markhuot\craftmcp\base\Plugin as BasePlugin;
use markhuot\craftmcp\tools\CreateEntry;
use markhuot\craftmcp\tools\GetFields;
use markhuot\craftmcp\tools\GetSections;
use markhuot\craftmcp\tools\SearchContent;
use markhuot\craftmcp\transports\StreamableHttpServerTransport;
use markhuot\craftmcp\session\CraftSessionHandler;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use markhuot\craftmcp\tools\UpdateEntry;
use PhpMcp\Schema\ServerCapabilities;
use PhpMcp\Server\Server;

class Plugin extends BasePlugin
{
    #[BindToContainer(singleton: true)]
    protected function registerMcpServer($container): Server
    {
        $capabilities = new ServerCapabilities(
            tools: true,
            resources: false,
            prompts: false,
        );

        $sessionHandler = $container->get(CraftSessionHandler::class);

        return Server::make()
            ->withServerInfo('Craft CMS MCP Server', '1.0.0')
            ->withCapabilities($capabilities)
            ->withSessionHandler($sessionHandler)
            ->withTool(SearchContent::class)
            ->withTool(GetSections::class)
            ->withTool(GetFields::class)
            ->withTool(CreateEntry::class)
            ->withTool(UpdateEntry::class)
            ->build();
    }

    #[BindToContainer(singleton: true)]
    protected function registerTransport($container): StreamableHttpServerTransport
    {
        $server = $container->get(Server::class);
        $sessionHandler = $container->get(CraftSessionHandler::class);
        $transport = new StreamableHttpServerTransport($sessionHandler);

        // Bind the protocol to the transport but don't start listening
        $protocol = $server->getProtocol();
        $protocol->bindTransport($transport);
        
        return $transport;
    }

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        $event->rules['GET mcp'] = 'mcp/mcp/listen';
        $event->rules['POST mcp'] = 'mcp/mcp/message';
        $event->rules['DELETE mcp'] = 'mcp/mcp/disconnect';
    }
}
