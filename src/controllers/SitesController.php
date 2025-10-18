<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\GetSites;
use yii\web\Response;

class SitesController extends Controller
{
    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetSites::class);
        return $this->callTool($tool->get(...), useQueryParams: true);
    }
}
