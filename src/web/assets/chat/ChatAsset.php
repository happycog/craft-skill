<?php

namespace happycog\craftmcp\web\assets\chat;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ChatAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@happycog/craftmcp/src/web/assets/chat/dist';
        $this->depends = [CpAsset::class];
        $this->js = ['chat.js'];
        $this->css = ['craft-skill-frontend.css'];

        parent::init();
    }
}
