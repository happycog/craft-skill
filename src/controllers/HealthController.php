<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\GetHealth;
use yii\web\Response;

class HealthController extends Controller
{
    public function actionIndex(): Response
    {
        $tool = \Craft::$container->get(GetHealth::class);
        return $this->callTool($tool->get(...));
    }
}
