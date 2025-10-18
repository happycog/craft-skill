<?php

namespace happycog\craftmcp\controllers;

use craft\web\Controller;
use happycog\craftmcp\tools\CreateEntryType;
use happycog\craftmcp\tools\DeleteEntryType;
use happycog\craftmcp\tools\GetEntryTypes;
use happycog\craftmcp\tools\UpdateEntryType;
use yii\web\Response;
use Craft;

class EntryTypesController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionCreate(): Response
    {
        $createEntryType = Craft::$container->get(CreateEntryType::class);
        $request = $this->request;
        $bodyParams = $request->getBodyParams();

        $result = $createEntryType->create(
            name: $bodyParams['name'] ?? '',
            handle: $bodyParams['handle'] ?? null,
            hasTitleField: $bodyParams['hasTitleField'] ?? true,
            titleTranslationMethod: $bodyParams['titleTranslationMethod'] ?? 'site',
            titleTranslationKeyFormat: $bodyParams['titleTranslationKeyFormat'] ?? null,
            titleFormat: $bodyParams['titleFormat'] ?? null,
            icon: $bodyParams['icon'] ?? null,
            color: $bodyParams['color'] ?? null,
            description: $bodyParams['description'] ?? null,
            showSlugField: $bodyParams['showSlugField'] ?? true,
            showStatusField: $bodyParams['showStatusField'] ?? true
        );

        return $this->asJson($result);
    }

    public function actionList(): Response
    {
        $getEntryTypes = Craft::$container->get(GetEntryTypes::class);
        $request = $this->request;
        $bodyParams = $request->getBodyParams();

        $result = $getEntryTypes->getAll(
            entryTypeIds: $bodyParams['entryTypeIds'] ?? null
        );

        return $this->asJson($result);
    }

    public function actionUpdate(): Response
    {
        $updateEntryType = Craft::$container->get(UpdateEntryType::class);
        $request = $this->request;
        $bodyParams = $request->getBodyParams();
        $entryTypeIdParam = $request->getQueryParam('id');
        throw_unless($entryTypeIdParam !== null && is_scalar($entryTypeIdParam), 'Entry type ID is required');
        $entryTypeId = (int) $entryTypeIdParam;

        $result = $updateEntryType->update(
            entryTypeId: $entryTypeId,
            name: $bodyParams['name'] ?? null,
            handle: $bodyParams['handle'] ?? null,
            titleTranslationMethod: $bodyParams['titleTranslationMethod'] ?? null,
            titleTranslationKeyFormat: $bodyParams['titleTranslationKeyFormat'] ?? null,
            titleFormat: $bodyParams['titleFormat'] ?? null,
            icon: $bodyParams['icon'] ?? null,
            color: $bodyParams['color'] ?? null,
            description: $bodyParams['description'] ?? null,
            showSlugField: $bodyParams['showSlugField'] ?? null,
            showStatusField: $bodyParams['showStatusField'] ?? null,
            fieldLayoutId: $bodyParams['fieldLayoutId'] ?? null
        );

        return $this->asJson($result);
    }

    public function actionDelete(): Response
    {
        $deleteEntryType = Craft::$container->get(DeleteEntryType::class);
        $request = $this->request;
        $entryTypeIdParam = $request->getQueryParam('id');
        throw_unless($entryTypeIdParam !== null && is_scalar($entryTypeIdParam), 'Entry type ID is required');
        $entryTypeId = (int) $entryTypeIdParam;
        $force = (bool) ($request->getBodyParam('force') ?? false);

        $result = $deleteEntryType->delete(
            entryTypeId: $entryTypeId,
            force: $force
        );

        return $this->asJson($result);
    }
}
