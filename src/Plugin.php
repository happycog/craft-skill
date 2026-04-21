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
    public bool $hasCpSection = true;

    /**
     * @return array<string, mixed>|null
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = 'AI';
        $item['url'] = 'ai';

        return $item;
    }

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        $mcpPath = trim($this->getSettings()->mcpPath ?? 'mcp', '/');

        // Streamable HTTP transport for the MCP server. The transport itself
        // dispatches on POST/DELETE/OPTIONS, so we route any method at the
        // configured path to a single controller action.
        $event->rules[$mcpPath] = 'skills/mcp/index';
    }

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES)]
    protected function registerCpUrlRules(RegisterUrlRulesEvent $event): void
    {
        $event->rules['ai'] = 'skills/ai/index';
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('skills/settings', [
            'settings' => $this->getSettings() ?? new Settings(),
        ]);
    }
}
