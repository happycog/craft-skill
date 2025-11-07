<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateAsset;
use happycog\craftmcp\tools\DeleteAsset;
use happycog\craftmcp\tools\GetVolumes;
use happycog\craftmcp\tools\UpdateAsset;
use yii\web\Response;

class AssetsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateAsset::class);
        return $this->callTool($tool->create(...));
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateAsset::class);
        return $this->callTool($tool->update(...), ['assetId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteAsset::class);
        return $this->callTool($tool->delete(...), ['assetId' => $id]);
    }

    public function actionVolumes(): Response
    {
        $tool = \Craft::$container->get(GetVolumes::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }
}
