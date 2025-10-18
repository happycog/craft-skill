<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\ApplyDraft;
use happycog\craftmcp\tools\CreateDraft;
use happycog\craftmcp\tools\UpdateDraft;
use yii\web\Response;

class DraftsController extends Controller
{
    public function actionCreate(): Response
    {
        $tool = \Craft::$container->get(CreateDraft::class);
        return $this->callTool($tool->create(...));
    }

    public function actionUpdate(int $id): Response
    {
        $tool = \Craft::$container->get(UpdateDraft::class);
        return $this->callTool($tool->update(...), ['draftId' => $id]);
    }

    public function actionApply(int $id): Response
    {
        $tool = \Craft::$container->get(ApplyDraft::class);
        return $this->callTool($tool->apply(...), ['draftId' => $id]);
    }
}
