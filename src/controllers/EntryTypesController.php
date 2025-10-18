<?php

namespace happycog\craftmcp\controllers;

use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\DeleteEntryType;
use happycog\craftmcp\tools\GetEntryTypes;
use happycog\craftmcp\tools\UpdateEntryType;
use yii\web\Response;

class EntryTypesController extends Controller
{
    public function actionCreate(CreateEntryType $createEntryType): Response
    {
        return $this->callTool($createEntryType->create(...));
    }

    public function actionList(GetEntryTypes $getEntryTypes): Response
    {
        return $this->callTool($getEntryTypes->getAll(...), useQueryParams: true);
    }

    public function actionUpdate(int $id, UpdateEntryType $updateEntryType): Response
    {
        $bodyParams = $this->request->getBodyParams();
        $bodyParams['entryTypeId'] = $id;
        
        $this->request->setBodyParams($bodyParams);
        return $this->callTool($updateEntryType->update(...));
    }

    public function actionDelete(int $id, DeleteEntryType $deleteEntryType): Response
    {
        $bodyParams = $this->request->getBodyParams();
        $bodyParams['entryTypeId'] = $id;
        
        $this->request->setBodyParams($bodyParams);
        return $this->callTool($deleteEntryType->delete(...));
    }
}
