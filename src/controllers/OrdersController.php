<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\GetOrder;
use happycog\craftmcp\tools\SearchOrders;
use happycog\craftmcp\tools\UpdateOrder;
use yii\web\Response;

class OrdersController extends Controller
{
    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetOrder::class);
        return $this->callTool($tool, ['orderId' => $id], useQueryParams: true);
    }

    public function actionSearch(): Response
    {
        $tool = \Craft::$container->get(SearchOrders::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateOrder::class);
        return $this->callTool($tool, ['orderId' => $id]);
    }
}
