<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateField;
use happycog\craftmcp\tools\DeleteField;
use happycog\craftmcp\tools\GetFields;
use happycog\craftmcp\tools\GetFieldTypes;
use happycog\craftmcp\tools\UpdateField;
use yii\web\Response;

class FieldsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateField::class);
        return $this->callTool($tool->create(...));
    }

    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetFields::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateField::class);
        return $this->callTool($tool->update(...), ['fieldId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteField::class);
        return $this->callTool($tool->delete(...), ['fieldId' => $id]);
    }

    public function actionTypes(): Response
    {
        $tool = \Craft::$container->get(GetFieldTypes::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }
}
