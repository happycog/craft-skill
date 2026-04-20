<?php

namespace happycog\craftmcp;

use Craft;
use happycog\craftmcp\base\Plugin as BasePlugin;
use happycog\craftmcp\attributes\RegisterListener;
use happycog\craftmcp\web\assets\chat\ChatAsset;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        $mcpPath = trim($this->getSettings()->mcpPath ?? 'mcp', '/');

        // Streamable HTTP transport for the MCP server. The transport itself
        // dispatches on POST/DELETE/OPTIONS, so we route any method at the
        // configured path to a single controller action.
        $event->rules[$mcpPath] = 'skills/mcp/index';
    }

    protected function settingsHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(ChatAsset::class);

        return Craft::$app->getView()->renderTemplate('skills/settings', [
            'settings' => $this->getSettings(),
        ]);
    }
}
