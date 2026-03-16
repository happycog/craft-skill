<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\GetStore;
use happycog\craftmcp\tools\GetStores;
use happycog\craftmcp\tools\UpdateStore;
use yii\web\Response;

class StoresController extends Controller
{
    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetStores::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetStore::class);
        return $this->callTool($tool, ['storeId' => $id], useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateStore::class);
        return $this->callTool($tool, ['storeId' => $id]);
    }
}
