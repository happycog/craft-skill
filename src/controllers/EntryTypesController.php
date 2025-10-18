<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\DeleteEntryType;
use happycog\craftmcp\tools\GetEntryTypes;
use happycog\craftmcp\tools\UpdateEntryType;
use yii\web\Response;

class EntryTypesController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateEntryType::class);
        return $this->callTool($tool->create(...));
    }

    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetEntryTypes::class);
        return $this->callTool($tool->getAll(...), useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateEntryType::class);
        return $this->callTool($tool->update(...), ['entryTypeId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteEntryType::class);
        return $this->callTool($tool->delete(...), ['entryTypeId' => $id]);
    }
}
