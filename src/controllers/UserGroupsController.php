<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateUserGroup;
use happycog\craftmcp\tools\DeleteUserGroup;
use happycog\craftmcp\tools\GetUserGroup;
use happycog\craftmcp\tools\GetUserGroups;
use happycog\craftmcp\tools\UpdateUserGroup;
use yii\web\Response;

class UserGroupsController extends Controller
{
    public function actionList(): Response
    {
        $tool = \Craft::$container->get(GetUserGroups::class);
        return $this->callTool($tool, useQueryParams: true);
    }

    public function actionGet(?int $id = null): Response
    {
        $tool = \Craft::$container->get(GetUserGroup::class);
        return $this->callTool($tool, ['groupId' => $id], useQueryParams: true);
    }

    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateUserGroup::class);
        return $this->callTool($tool);
    }

    public function actionUpdate(?int $id = null): Response
    {
        $tool = \Craft::$container->get(UpdateUserGroup::class);
        return $this->callTool($tool, ['groupId' => $id]);
    }

    public function actionDelete(?int $id = null): Response
    {
        $tool = \Craft::$container->get(DeleteUserGroup::class);
        return $this->callTool($tool, ['groupId' => $id]);
    }
}
