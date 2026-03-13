<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateUser;
use happycog\craftmcp\tools\DeleteUser;
use happycog\craftmcp\tools\GetAvailablePermissions;
use happycog\craftmcp\tools\GetUser;
use happycog\craftmcp\tools\GetUserFieldLayout;
use happycog\craftmcp\tools\GetUsers;
use happycog\craftmcp\tools\UpdateUser;
use yii\web\Response;

class UsersController extends Controller
{
    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetUsers::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionGet(?int $id = null): Response
    {
        $tool = \Craft::$container->get(GetUser::class);
        return $this->callTool($tool, ['userId' => $id], useQueryParams: true);
    }

    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateUser::class);
        return $this->callTool($tool);
    }

    public function actionPermissions(): Response
    {
        $tool = \Craft::$container->get(GetAvailablePermissions::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionUpdate(?int $id = null): Response
    {
        $tool = \Craft::$container->get(UpdateUser::class);
        return $this->callTool($tool, ['userId' => $id]);
    }

    public function actionDelete(?int $id = null): Response
    {
        $tool = \Craft::$container->get(DeleteUser::class);
        return $this->callTool($tool, ['userId' => $id]);
    }

    public function actionFieldLayout(): Response
    {
        $tool = \Craft::$container->get(GetUserFieldLayout::class);
        return $this->callTool($tool, useQueryParams: true);
    }
}
