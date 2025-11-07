<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Asset;
use happycog\craftmcp\exceptions\ModelSaveException;

class DeleteAsset
{
    /**
     * Delete an asset from Craft CMS.
     *
     * This permanently deletes the asset element and its associated file from the volume.
     * This action cannot be undone.
     *
     * **Warning:**
     * - Deleting an asset removes the file from storage
     * - Any entries or fields referencing this asset will lose the reference
     * - This operation is permanent and cannot be undone
     *
     * **Deletion Behavior:**
     * - Asset element is removed from Craft's database
     * - Physical file is deleted from the volume
     * - Any relations to this asset are automatically cleaned up
     *
     * @return array<string, mixed>
     */
    public function delete(
        /** The ID of the asset to delete */
        int $assetId,
    ): array
    {
        // Get the asset to delete
        $asset = Asset::find()->id($assetId)->one();
        throw_unless(
            $asset instanceof Asset,
            \InvalidArgumentException::class,
            "Asset with ID {$assetId} not found"
        );

        // Capture asset information before deletion for response
        $assetInfo = [
            'assetId' => $asset->id,
            'title' => $asset->title,
            'filename' => $asset->filename,
            'volumeId' => $asset->volumeId,
            'kind' => $asset->kind,
        ];

        // Delete the asset
        throw_unless(
            Craft::$app->getElements()->deleteElement($asset, true),
            ModelSaveException::class,
            $asset
        );

        return [
            '_notes' => 'The asset was successfully deleted.',
            ...$assetInfo,
        ];
    }
}
