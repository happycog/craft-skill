<?php

namespace happycog\craftmcp\web\assets\chat;

use Craft;
use craft\web\AssetBundle;

class ChatAsset extends AssetBundle
{
    private const SOURCE_PATH = '@happycog/craftmcp/web/assets/chat/dist';

    public function init(): void
    {
        $this->sourcePath = self::SOURCE_PATH;
        $this->js = ['chat.js'];

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->publishOptions = ['forceCopy' => true];
        }

        parent::init();
    }

    public static function publishedScriptUrl(): string
    {
        $publishOptions = Craft::$app->getConfig()->getGeneral()->devMode
            ? ['forceCopy' => true]
            : [];

        [, $url] = Craft::$app->getAssetManager()->publish(
            Craft::getAlias(self::SOURCE_PATH),
            $publishOptions,
        );

        return rtrim($url, '/') . '/chat.js';
    }
}
