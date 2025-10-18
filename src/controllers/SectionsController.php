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

    public function actionUpdate(int $id, UpdateSection $updateSection): Response
    {
        $bodyParams = $this->request->getBodyParams();
        $bodyParams['sectionId'] = $id;
        
        $this->request->setBodyParams($bodyParams);
        return $this->callTool($updateSection->update(...));
    }

    public function actionDelete(int $id, DeleteSection $deleteSection): Response
    {
        $bodyParams = $this->request->getBodyParams();
        $bodyParams['sectionId'] = $id;
        
        $this->request->setBodyParams($bodyParams);
        return $this->callTool($deleteSection->delete(...));
    }
}
