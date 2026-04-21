<?php

namespace happycog\craftmcp;

use Craft;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\events\RegisterUrlRulesEvent;
use craft\web\Request;
use craft\web\UrlManager;
use craft\web\View;
use happycog\craftmcp\attributes\RegisterListener;
use happycog\craftmcp\base\Plugin as BasePlugin;
use happycog\craftmcp\llm\LlmManager;
use happycog\craftmcp\web\assets\chat\ChatAsset;
use yii\helpers\Html;

class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;
    public bool $hasCpSection = false;

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        $mcpPath = trim($this->getSettings()->mcpPath ?? 'mcp', '/');

        // Streamable HTTP transport for the MCP server. The transport itself
        // dispatches on POST/DELETE/OPTIONS, so we route any method at the
        // configured path to a single controller action.
        $event->rules[$mcpPath] = 'skills/mcp/index';
    }

    #[RegisterListener(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE)]
    protected function injectChatUi(TemplateEvent $event): void
    {
        if (!str_contains($event->output, '<body')) {
            return;
        }

        if (str_contains($event->output, '<craft-skill-chat')) {
            return;
        }

        /** @var LlmManager $llm */
        $llm = Craft::$container->get(LlmManager::class);
        $request = Craft::$app->getRequest();

        if (!$request instanceof Request) {
            return;
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canChat = $currentUser?->can('accessCp') ?? false;

        $chatHost = Html::tag('craft-skill-chat', '', [
            'data-chat-url' => UrlHelper::actionUrl('skills/chat/stream'),
            'data-can-chat' => $canChat ? '1' : '0',
            'data-configured' => $llm->isConfigured() ? '1' : '0',
            'data-context' => $request->getIsCpRequest() ? 'cp' : 'site',
            'data-current-url' => $request->getAbsoluteUrl(),
        ]);

        $scriptTag = Html::script('', [
            'defer' => true,
            'src' => ChatAsset::publishedScriptUrl(),
        ]);

        $injection = $chatHost . $scriptTag;
        $bodyClosePosition = strripos($event->output, '</body>');

        if ($bodyClosePosition === false) {
            $event->output .= $injection;

            return;
        }

        $event->output = substr_replace($event->output, $injection . '</body>', $bodyClosePosition, 7);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('skills/settings', [
            'settings' => $this->getSettings() ?? new Settings(),
        ]);
    }
}
