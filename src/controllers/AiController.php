<?php

declare(strict_types=1);

namespace happycog\craftmcp\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller as CraftController;
use happycog\craftmcp\llm\LlmManager;
use happycog\craftmcp\web\assets\chat\ChatAsset;
use yii\web\Response;

/**
 * Full-page CP controller for the AI chat interface.
 *
 * Renders at /admin/ai as its own CP section, outside of
 * Craft's plugin-settings form wrapper.
 */
class AiController extends CraftController
{
    public function actionIndex(): Response
    {
        Craft::$app->getView()->registerAssetBundle(ChatAsset::class);

        /** @var LlmManager $llm */
        $llm = Craft::$container->get(LlmManager::class);

        return $this->renderTemplate('skills/ai/index', [
            'title'      => 'AI',
            'chatUrl'    => UrlHelper::actionUrl('skills/chat/stream'),
            'configured' => $llm->isConfigured(),
        ]);
    }
}
