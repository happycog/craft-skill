<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use happycog\craftmcp\exceptions\ModelSaveException;

class UpdateAsset
{
    /**
     * Update an existing asset in Craft CMS.
     *
     * This tool allows updating asset metadata (title, alt text, filename) and optionally
     * replacing the physical file with a new one from a local or remote URL.
     *
     * **Metadata Updates:**
     * - Update title, filename, or custom field values like alt text
     * - Changes are applied immediately to the asset
     *
     * **File Replacement:**
     * - Provide newFileUrl to replace the physical file
     * - Supports local file:// paths and remote http(s):// URLs
     * - Original file is replaced while maintaining asset ID and metadata
     *
     * **Field Updates:**
     * - Update custom fields through the fieldData parameter
     * - Pass field values keyed by field handle (e.g., {"alt": "New alt text"})
     *
     * After updating the asset always link the user back to the asset in the Craft control panel
     * so they can review the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $fieldData
     * @return array<string, mixed>
     */
    public function update(
        /** The ID of the asset to update */
        int $assetId,

        /** Optional new title for the asset */
        ?string $title = null,

        /** Optional new filename (will be sanitized by Craft) */
        ?string $filename = null,

        /** Optional new file URL to replace the asset file (local file:// or remote http(s)://) */
        ?string $newFileUrl = null,

        /** Optional field data to update custom fields (e.g., {"alt": "New alt text"}) */
        array $fieldData = [],
    ): array
    {
        $assetsService = Craft::$app->getAssets();

        // Get the existing asset
        $asset = Asset::find()->id($assetId)->one();
        throw_unless(
            $asset instanceof Asset,
            \InvalidArgumentException::class,
            "Asset with ID {$assetId} not found"
        );

        // Update basic properties
        if ($title !== null) {
            $asset->title = $title;
        }

        if ($filename !== null) {
            $asset->newFilename = AssetsHelper::prepareAssetName($filename);
        }

        // Update custom field values
        foreach ($fieldData as $handle => $value) {
            $asset->setFieldValue($handle, $value);
        }

        // Replace file if new URL provided
        if ($newFileUrl !== null) {
            $tempDir = Craft::$app->getPath()->getTempPath() . '/asset-uploads';
            FileHelper::createDirectory($tempDir);

            $tempFilePath = '';
            try {
                // Use the same download logic as CreateAsset
                $createAsset = new CreateAsset();
                $tempFilePath = $this->downloadFile($newFileUrl, $tempDir);

                // Set the new file path
                $asset->tempFilePath = $tempFilePath;
                $asset->setScenario(Asset::SCENARIO_REPLACE);
            } catch (\Throwable $e) {
                // Clean up temp file on error
                if ($tempFilePath && file_exists($tempFilePath)) {
                    FileHelper::unlink($tempFilePath);
                }
                throw $e;
            }
        }

        // Save the asset
        throw_unless(
            Craft::$app->getElements()->saveElement($asset),
            ModelSaveException::class,
            $asset
        );

        // Clean up temp file after successful save
        if ($newFileUrl !== null && $tempFilePath && file_exists($tempFilePath)) {
            FileHelper::unlink($tempFilePath);
        }

        return [
            '_notes' => 'The asset was successfully updated.',
            'assetId' => $asset->id,
            'title' => $asset->title,
            'filename' => $asset->filename,
            'volumeId' => $asset->volumeId,
            'folderId' => $asset->folderId,
            'kind' => $asset->kind,
            'size' => $asset->size,
            'extension' => $asset->extension,
            'url' => $asset->getUrl(),
            'cpEditUrl' => $asset->getCpEditUrl(),
        ];
    }

    /**
     * Download a file from URL or copy from local path to temp directory.
     *
     * @param string $fileUrl The file URL (local file:// or remote http(s)://)
     * @param string $tempDir The temporary directory to store the file
     * @return string The path to the downloaded file
     * @throws \InvalidArgumentException
     */
    private function downloadFile(string $fileUrl, string $tempDir): string
    {
        $parsedUrl = parse_url($fileUrl);
        $scheme = $parsedUrl['scheme'] ?? '';

        // Handle local file paths
        if ($scheme === 'file' || $scheme === '') {
            $localPath = $scheme === 'file' ? ($parsedUrl['path'] ?? '') : $fileUrl;

            // Remove leading slash on Windows for file:// URLs
            if (DIRECTORY_SEPARATOR === '\\' && preg_match('/^\/[a-zA-Z]:/', $localPath)) {
                $localPath = substr($localPath, 1);
            }

            throw_unless(
                file_exists($localPath),
                \InvalidArgumentException::class,
                "Local file does not exist: {$localPath}"
            );

            throw_unless(
                is_readable($localPath),
                \InvalidArgumentException::class,
                "Local file is not readable: {$localPath}"
            );

            // Copy to temp directory
            $tempFilePath = $tempDir . '/' . uniqid('asset_') . '_' . basename($localPath);
            throw_unless(
                copy($localPath, $tempFilePath),
                \RuntimeException::class,
                "Failed to copy local file to temp directory"
            );

            return $tempFilePath;
        }

        // Handle remote URLs
        if ($scheme === 'http' || $scheme === 'https') {
            $tempFilePath = $tempDir . '/' . uniqid('asset_') . '_' . basename($parsedUrl['path'] ?? 'download');

            // Download the file
            $client = Craft::createGuzzleClient();
            try {
                $response = $client->request('GET', $fileUrl, [
                    'sink' => $tempFilePath,
                    'timeout' => 30,
                ]);

                throw_unless(
                    $response->getStatusCode() === 200,
                    \RuntimeException::class,
                    "Failed to download file from {$fileUrl}: HTTP {$response->getStatusCode()}"
                );

                return $tempFilePath;
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                throw new \RuntimeException("Failed to download file from {$fileUrl}: {$e->getMessage()}", 0, $e);
            }
        }

        throw new \InvalidArgumentException(
            "Unsupported file URL scheme: {$scheme}. Only file://, http://, and https:// are supported."
        );
    }
}
