<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateEntry;
use happycog\craftmcp\tools\DeleteEntry;
use happycog\craftmcp\tools\GetEntry;
use happycog\craftmcp\tools\SearchContent;
use happycog\craftmcp\tools\UpdateEntry;
use yii\web\Response;

class EntriesController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateEntry::class);
        return $this->callTool($tool->create(...));
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetEntry::class);
        return $this->callTool($tool->get(...), ['entryId' => $id], useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateEntry::class);
        return $this->callTool($tool->update(...), ['entryId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteEntry::class);
        return $this->callTool($tool->delete(...), ['entryId' => $id]);
    }

    public function actionSearch(): Response
    {
        $tool = \Craft::$container->get(SearchContent::class);
        return $this->callTool($tool->search(...), useQueryParams: true);
    }
}
