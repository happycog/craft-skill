<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateFieldLayout;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\UpdateFieldLayout;
use yii\web\Response;

class FieldLayoutsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateFieldLayout::class);
        return $this->callTool($tool->create(...));
    }

    public function actionGet(): Response
    {
        $tool = \Craft::$container->get(GetFieldLayout::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateFieldLayout::class);
        return $this->callTool($tool->update(...), ['fieldLayoutId' => $id]);
    }
}
