<?php

namespace happycog\craftmcp\tools;

use Craft;

class GetVolumes
{
    /**
     * Get information about asset volumes in Craft CMS.
     *
     * Asset volumes define where uploaded files are stored. Each volume represents a storage location
     * (local filesystem, Amazon S3, etc.) where assets can be organized into folders.
     *
     * Use this tool to discover available volumes before uploading assets with the CreateAsset tool.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $volumesService = Craft::$app->getVolumes();
        $allVolumes = $volumesService->getAllVolumes();

        $volumes = [];
        foreach ($allVolumes as $volume) {
            $volumes[] = [
                'id' => $volume->id,
                'name' => $volume->name,
                'handle' => $volume->handle,
                'type' => get_class($volume),
                'hasUrls' => $volume->getFs()->hasUrls ?? false,
                'url' => $volume->getFs()->hasUrls ? $volume->getFs()->getRootUrl() : null,
            ];
        }

        return [
            '_notes' => 'Retrieved all asset volumes.',
            'volumes' => $volumes,
        ];
    }
}
