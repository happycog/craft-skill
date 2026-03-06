<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateProduct;
use happycog\craftmcp\tools\DeleteProduct;
use happycog\craftmcp\tools\GetProduct;
use happycog\craftmcp\tools\GetProducts;
use happycog\craftmcp\tools\GetProductTypes;
use happycog\craftmcp\tools\UpdateProduct;
use yii\web\Response;

class ProductsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateProduct::class);
        return $this->callTool($tool);
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetProduct::class);
        return $this->callTool($tool, ['productId' => $id], useQueryParams: true);
    }

    public function actionSearch(): Response
    {
        $tool = \Craft::$container->get(GetProducts::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateProduct::class);
        return $this->callTool($tool, ['productId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteProduct::class);
        return $this->callTool($tool, ['productId' => $id]);
    }

    public function actionTypes(): Response
    {
        $tool = \Craft::$container->get(GetProductTypes::class);
        return $this->callTool($tool, useQueryParams: true);
    }
}
