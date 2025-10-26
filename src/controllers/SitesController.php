<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateSite;
use happycog\craftmcp\tools\GetSites;
use happycog\craftmcp\tools\UpdateSite;
use yii\web\Response;

class SitesController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateSite::class);
        return $this->callTool($tool->create(...));
    }

    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetSites::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateSite::class);
        return $this->callTool($tool->update(...), ['siteId' => $id]);
    }
}
