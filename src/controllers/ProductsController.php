<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateProduct;
use happycog\craftmcp\tools\CreateProductType;
use happycog\craftmcp\tools\DeleteProduct;
use happycog\craftmcp\tools\DeleteProductType;
use happycog\craftmcp\tools\GetProduct;
use happycog\craftmcp\tools\GetProducts;
use happycog\craftmcp\tools\GetProductType;
use happycog\craftmcp\tools\GetProductTypes;
use happycog\craftmcp\tools\UpdateProduct;
use happycog\craftmcp\tools\UpdateProductType;
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

    public function actionCreateType(): Response
    {
        $tool = \Craft::$container->get(CreateProductType::class);
        return $this->callTool($tool);
    }

    public function actionGetType(int $id): Response
    {
        $tool = \Craft::$container->get(GetProductType::class);
        return $this->callTool($tool, ['productTypeId' => $id], useQueryParams: true);
    }

    public function actionUpdateType(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateProductType::class);
        return $this->callTool($tool, ['productTypeId' => $id]);
    }

    public function actionDeleteType(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteProductType::class);
        return $this->callTool($tool, ['productTypeId' => $id]);
    }
}
