<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use happycog\craftmcp\exceptions\ModelSaveException;

class CreateAsset
{
    /**
     * Upload a file and create an asset in Craft CMS.
     *
     * This tool accepts either a local file path (e.g., file:///path/to/file.jpg) or a remote URL
     * (e.g., https://example.com/image.jpg) and uploads it to a Craft asset volume.
     *
     * **File Handling:**
     * - For local files: Provide the full file:// URL or absolute path
     * - For remote files: Provide the http:// or https:// URL
     * - The file will be downloaded to a temporary directory within the project before upload
     * - Supported file types depend on the target volume's configuration
     *
     * **Volume Selection:**
     * - Use volumeId to specify the target volume
     * - Use GetVolumes tool to discover available volumes and their IDs
     *
     * **Folder Organization:**
     * - Optional folderId to organize assets within the volume
     * - If not provided, assets are placed in the volume root
     *
     * After creating the asset always link the user back to the asset in the Craft control panel
     * so they can review the upload in the context of the Craft UI.
     *
     * @return array<string, mixed>
     */
    public function create(
        /** The file URL to upload - either local file:// path or remote http(s):// URL */
        string $fileUrl,

        /** The ID of the asset volume to upload to */
        int $volumeId,

        /** Optional title for the asset - defaults to filename if not provided */
        ?string $title = null,

        /** Optional folder ID within the volume - defaults to volume root */
        ?int $folderId = null,
    ): array
    {
        $assetsService = Craft::$app->getAssets();
        $volumesService = Craft::$app->getVolumes();

        // Validate volume exists
        $volume = $volumesService->getVolumeById($volumeId);
        throw_unless($volume, \InvalidArgumentException::class, "Volume with ID {$volumeId} does not exist.");

        // Get folder - either specified or volume root
        if ($folderId) {
            $folder = $assetsService->getFolderById($folderId);
            throw_unless($folder, \InvalidArgumentException::class, "Folder with ID {$folderId} does not exist.");
        } else {
            $folder = $assetsService->getRootFolderByVolumeId($volumeId);
            throw_unless($folder, "Could not find root folder for volume {$volumeId}");
        }

        // Download file to temporary location within project
        $tempDir = Craft::$app->getPath()->getTempPath() . '/asset-uploads';
        FileHelper::createDirectory($tempDir);

        $tempFilePath = $this->downloadFile($fileUrl, $tempDir);

        try {
            // Extract filename from URL or path
            $urlPath = parse_url($fileUrl, PHP_URL_PATH);
            $originalFilename = basename($urlPath ?: 'upload');
            $filename = AssetsHelper::prepareAssetName($originalFilename);

            // Create the asset element
            $asset = new Asset();
            $asset->tempFilePath = $tempFilePath;
            $asset->filename = $filename;
            $asset->newFolderId = $folder->id;
            $asset->volumeId = $volumeId;
            $asset->avoidFilenameConflicts = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);

            // Set optional metadata
            if ($title) {
                $asset->title = $title;
            }

            // Save the asset
            throw_unless(
                Craft::$app->getElements()->saveElement($asset),
                ModelSaveException::class,
                $asset
            );

            return [
                '_notes' => 'The asset was successfully uploaded and created.',
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
        } finally {
            // Clean up temp file
            if (file_exists($tempFilePath)) {
                FileHelper::unlink($tempFilePath);
            }
        }
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
