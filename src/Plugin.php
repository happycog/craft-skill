<?php

namespace markhuot\craftmcp;

use markhuot\craftmcp\attributes\Init;
use markhuot\craftmcp\attributes\BindToContainer;
use markhuot\craftmcp\attributes\RegisterListener;
use markhuot\craftmcp\base\Plugin as BasePlugin;
use markhuot\craftmcp\tools\CreateEntry;
use markhuot\craftmcp\tools\GetFields;
use markhuot\craftmcp\tools\GetSections;
use markhuot\craftmcp\tools\SearchContent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use markhuot\craftmcp\tools\UpdateEntry;
use PhpMcp\Server\Model\Capabilities;
use PhpMcp\Server\Server;
use yii\base\Event;

class Plugin extends BasePlugin
{
    #[BindToContainer(singleton: true)]
    protected function registerMcpServer(): Server
    {
        $capabilities = Capabilities::forServer(
            toolsEnabled: true,
            resourcesEnabled: false,
            promptsEnabled: false,
        );

        return Server::make()
            ->withServerInfo('Craft CMS MCP Server', '1.0.0')
            ->withCapabilities($capabilities)
            ->withTool(SearchContent::class)
            ->withTool(GetSections::class)
            ->withTool(GetFields::class)
            ->withTool(CreateEntry::class)
            ->withTool(UpdateEntry::class)
            ->build();
    }

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        //$event->rules['GET mcp'] = 'mcp/mcp/listen';
        $event->rules['POST mcp'] = 'mcp/mcp/message';
    }
}
