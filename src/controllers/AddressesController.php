<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateAddress;
use happycog\craftmcp\tools\DeleteAddress;
use happycog\craftmcp\tools\GetAddress;
use happycog\craftmcp\tools\GetAddressFieldLayout;
use happycog\craftmcp\tools\GetAddresses;
use happycog\craftmcp\tools\UpdateAddress;
use yii\web\Response;

class AddressesController extends Controller
{
    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetAddresses::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionGet(int $id): Response
    {
        $tool = \Craft::$container->get(GetAddress::class);
        return $this->callTool($tool, ['addressId' => $id], useQueryParams: true);
    }

    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateAddress::class);
        return $this->callTool($tool);
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateAddress::class);
        return $this->callTool($tool, ['addressId' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $tool = \Craft::$container->get(DeleteAddress::class);
        return $this->callTool($tool, ['addressId' => $id]);
    }

    public function actionFieldLayout(): Response
    {
        $tool = \Craft::$container->get(GetAddressFieldLayout::class);
        return $this->callTool($tool, useQueryParams: true);
    }
}
