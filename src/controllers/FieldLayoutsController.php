<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\AddFieldToFieldLayout;
use happycog\craftmcp\tools\AddTabToFieldLayout;
use happycog\craftmcp\tools\AddUiElementToFieldLayout;
use happycog\craftmcp\tools\CreateFieldLayout;
use happycog\craftmcp\tools\GetFieldLayout;
use happycog\craftmcp\tools\MoveElementInFieldLayout;
use happycog\craftmcp\tools\RemoveElementFromFieldLayout;
use yii\web\Response;

class FieldLayoutsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateFieldLayout::class);
        return $this->callTool($tool(...));
    }

    public function actionGet(): Response
    {
        $tool = \Craft::$container->get(GetFieldLayout::class);
        return $this->callTool($tool(...), useQueryParams: true);
    }

    public function actionAddTab(int $id): Response
    {
        $tool = \Craft::$container->get(AddTabToFieldLayout::class);
        return $this->callTool($tool(...), ['fieldLayoutId' => $id]);
    }

    public function actionAddField(int $id): Response
    {
        $tool = \Craft::$container->get(AddFieldToFieldLayout::class);
        return $this->callTool($tool(...), ['fieldLayoutId' => $id]);
    }

    public function actionAddUiElement(int $id): Response
    {
        $tool = \Craft::$container->get(AddUiElementToFieldLayout::class);
        return $this->callTool($tool(...), ['fieldLayoutId' => $id]);
    }

    public function actionRemoveElement(int $id): Response
    {
        $tool = \Craft::$container->get(RemoveElementFromFieldLayout::class);
        return $this->callTool($tool(...), ['fieldLayoutId' => $id]);
    }

    public function actionMoveElement(int $id): Response
    {
        $tool = \Craft::$container->get(MoveElementInFieldLayout::class);
        return $this->callTool($tool(...), ['fieldLayoutId' => $id]);
    }
}
