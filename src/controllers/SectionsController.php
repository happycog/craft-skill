<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\DeleteSection;
use happycog\craftmcp\tools\GetSection;
use happycog\craftmcp\tools\GetSections;
use happycog\craftmcp\tools\UpdateSection;
use yii\web\Response;

class SectionsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateSection::class);
        return $this->callTool($tool);
    }

    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetSections::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetSection::class);
        return $this->callTool($tool, ['sectionId' => $id]);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateSection::class);
        return $this->callTool($tool, ['sectionId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteSection::class);
        return $this->callTool($tool, ['sectionId' => $id]);
    }
}
