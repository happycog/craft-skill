<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\DeleteSection;
use happycog\craftmcp\tools\GetSections;
use happycog\craftmcp\tools\UpdateSection;
use yii\web\Response;

class SectionsController extends Controller
{
    public function actionCreate(CreateSection $createSection): Response
    {
        return $this->callTool($createSection->create(...));
    }

    public function actionList(GetSections $getSections): Response
    {
        return $this->callTool($getSections->get(...), useQueryParams: true);
    }

    public function actionUpdate(UpdateSection $updateSection): Response
    {
        return $this->callTool($updateSection->update(...));
    }

    public function actionDelete(DeleteSection $deleteSection): Response
    {
        return $this->callTool($deleteSection->delete(...));
    }
}
