<?php

namespace happycog\craftmcp\web\assets\chat;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ChatAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@happycog/craftmcp/web/assets/chat/dist';
        $this->depends = [CpAsset::class];
        $this->js = ['chat.js'];
        $this->css = ['chat.css'];

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->publishOptions = ['forceCopy' => true];
        }

        parent::init();
    }
}
