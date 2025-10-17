<?php

namespace happycog\craftmcp\controllers;

use craft\web\Controller;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\DeleteSection;
use happycog\craftmcp\tools\GetSections;
use happycog\craftmcp\tools\UpdateSection;
use yii\web\Response;
use Craft;

class SectionsController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionCreate(): Response
    {
        $createSection = Craft::$container->get(CreateSection::class);
        $request = $this->request;
        $bodyParams = $request->getBodyParams();

        $result = $createSection->create(
            name: $bodyParams['name'] ?? '',
            type: $bodyParams['type'] ?? '',
            entryTypeIds: $bodyParams['entryTypeIds'] ?? [],
            handle: $bodyParams['handle'] ?? null,
            enableVersioning: $bodyParams['enableVersioning'] ?? true,
            propagationMethod: $bodyParams['propagationMethod'] ?? 'all',
            maxLevels: $bodyParams['maxLevels'] ?? null,
            defaultPlacement: $bodyParams['defaultPlacement'] ?? 'end',
            maxAuthors: $bodyParams['maxAuthors'] ?? null,
            siteSettings: $bodyParams['siteSettings'] ?? null
        );

        return $this->asJson($result);
    }

    public function actionList(): Response
    {
        $getSections = Craft::$container->get(GetSections::class);
        $request = $this->request;
        $sectionIds = $request->getQueryParam('sectionIds');

        $result = $getSections->get(
            sectionIds: $sectionIds
        );

        return $this->asJson($result);
    }

    public function actionUpdate(): Response
    {
        $updateSection = Craft::$container->get(UpdateSection::class);
        $request = $this->request;
        $bodyParams = $request->getBodyParams();
        $sectionIdParam = $request->getQueryParam('id');
        throw_unless($sectionIdParam !== null && is_scalar($sectionIdParam), 'Section ID is required');
        $sectionId = (int) $sectionIdParam;

        $result = $updateSection->update(
            sectionId: $sectionId,
            name: $bodyParams['name'] ?? null,
            handle: $bodyParams['handle'] ?? null,
            type: $bodyParams['type'] ?? null,
            entryTypeIds: $bodyParams['entryTypeIds'] ?? null,
            enableVersioning: $bodyParams['enableVersioning'] ?? null,
            propagationMethod: $bodyParams['propagationMethod'] ?? null,
            maxLevels: $bodyParams['maxLevels'] ?? null,
            defaultPlacement: $bodyParams['defaultPlacement'] ?? null,
            maxAuthors: $bodyParams['maxAuthors'] ?? null,
            siteSettingsData: $bodyParams['siteSettings'] ?? null
        );

        return $this->asJson($result);
    }

    public function actionDelete(): Response
    {
        $deleteSection = Craft::$container->get(DeleteSection::class);
        $request = $this->request;
        $sectionIdParam = $request->getQueryParam('id');
        throw_unless($sectionIdParam !== null && is_scalar($sectionIdParam), 'Section ID is required');
        $sectionId = (int) $sectionIdParam;
        $force = (bool) ($request->getBodyParam('force') ?? false);

        $result = $deleteSection->delete(
            sectionId: $sectionId,
            force: $force
        );

        return $this->asJson($result);
    }
}
