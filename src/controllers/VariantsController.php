<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\GetVariant;
use happycog\craftmcp\tools\UpdateVariant;
use yii\web\Response;

class VariantsController extends Controller
{
    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetVariant::class);
        return $this->callTool($tool, ['variantId' => $id], useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateVariant::class);
        return $this->callTool($tool, ['variantId' => $id]);
    }
}
