<?php

use craft\elements\Asset;
use craft\helpers\FileHelper;
use happycog\craftmcp\tools\CreateAsset;
use happycog\craftmcp\tools\DeleteAsset;
use happycog\craftmcp\tools\GetVolumes;
use happycog\craftmcp\tools\UpdateAsset;

beforeEach(function () {
    // Get first available volume for testing
    $this->volume = Craft::$app->getVolumes()->getAllVolumes()[0] ?? null;

    if (!$this->volume) {
        $this->markTestSkipped('No asset volumes available for testing');
    }

    $this->volumeId = $this->volume->id;
    $this->createdAssetIds = [];

    // Create a test file in project temp directory
    $this->testFilePath = Craft::$app->getPath()->getTempPath() . '/test-upload.txt';
    FileHelper::writeToFile($this->testFilePath, 'Test file content for asset upload');
});

afterEach(function () {
    // Clean up created assets
    foreach ($this->createdAssetIds as $assetId) {
        $asset = Asset::find()->id($assetId)->one();
        if ($asset) {
            Craft::$app->getElements()->deleteElement($asset, true);
        }
    }

    // Clean up test file
    if (isset($this->testFilePath) && file_exists($this->testFilePath)) {
        FileHelper::unlink($this->testFilePath);
    }
});

test('can get all volumes', function () {
    $getVolumes = Craft::$container->get(GetVolumes::class);

    $response = $getVolumes->get();

    expect($response)->toHaveKey('_notes');
    expect($response)->toHaveKey('volumes');
    expect($response['volumes'])->toBeArray();

    if (count($response['volumes']) > 0) {
        $volume = $response['volumes'][0];
        expect($volume)->toHaveKey('id');
        expect($volume)->toHaveKey('name');
        expect($volume)->toHaveKey('handle');
        expect($volume)->toHaveKey('type');
    }
});

test('can create asset from local file path', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    $response = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
        title: 'Test Upload',
    );

    expect($response)->toHaveKey('_notes');
    expect($response)->toHaveKey('assetId');
    expect($response)->toHaveKey('title');
    expect($response)->toHaveKey('filename');
    expect($response)->toHaveKey('cpEditUrl');
    expect($response['title'])->toBe('Test Upload');

    $this->createdAssetIds[] = $response['assetId'];

    // Verify asset was created in database
    $asset = Asset::find()->id($response['assetId'])->one();
    expect($asset)->toBeInstanceOf(Asset::class);
    expect($asset->title)->toBe('Test Upload');
    expect($asset->volumeId)->toBe($this->volumeId);
});

test('can create asset from file:// URL', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    $fileUrl = 'file://' . $this->testFilePath;

    $response = $createAsset->create(
        fileUrl: $fileUrl,
        volumeId: $this->volumeId,
    );

    expect($response)->toHaveKey('assetId');
    expect($response)->toHaveKey('filename');

    $this->createdAssetIds[] = $response['assetId'];

    // Verify asset was created
    $asset = Asset::find()->id($response['assetId'])->one();
    expect($asset)->toBeInstanceOf(Asset::class);
});

test('can update asset title', function () {
    // First create an asset
    $createAsset = Craft::$container->get(CreateAsset::class);
    $createResponse = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
        title: 'Original Title',
    );

    $this->createdAssetIds[] = $createResponse['assetId'];

    // Now update it
    $updateAsset = Craft::$container->get(UpdateAsset::class);
    $response = $updateAsset->update(
        assetId: $createResponse['assetId'],
        title: 'Updated Title',
    );

    expect($response)->toHaveKey('_notes');
    expect($response)->toHaveKey('assetId');
    expect($response['title'])->toBe('Updated Title');

    // Verify in database
    $asset = Asset::find()->id($createResponse['assetId'])->one();
    expect($asset)->toBeInstanceOf(Asset::class);
    expect($asset->title)->toBe('Updated Title');
});

test('can update asset with empty field data', function () {
    // First create an asset
    $createAsset = Craft::$container->get(CreateAsset::class);
    $createResponse = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
    );

    $this->createdAssetIds[] = $createResponse['assetId'];

    // Update with empty field data (just testing it works without errors)
    $updateAsset = Craft::$container->get(UpdateAsset::class);
    $response = $updateAsset->update(
        assetId: $createResponse['assetId'],
        fieldData: [],
    );

    expect($response)->toHaveKey('assetId');
    expect($response['assetId'])->toBe($createResponse['assetId']);
});test('can update asset filename', function () {
    // First create an asset
    $createAsset = Craft::$container->get(CreateAsset::class);
    $createResponse = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
    );

    $this->createdAssetIds[] = $createResponse['assetId'];

    // Update filename
    $updateAsset = Craft::$container->get(UpdateAsset::class);
    $response = $updateAsset->update(
        assetId: $createResponse['assetId'],
        filename: 'renamed-file.txt',
    );

    expect($response)->toHaveKey('filename');
    expect($response['filename'])->toBe('renamed-file.txt');

    // Verify in database
    $asset = Asset::find()->id($createResponse['assetId'])->one();
    expect($asset)->toBeInstanceOf(Asset::class);
    expect($asset->filename)->toBe('renamed-file.txt');
});

test('can delete asset', function () {
    // First create an asset
    $createAsset = Craft::$container->get(CreateAsset::class);
    $createResponse = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
        title: 'To Be Deleted',
    );

    $assetId = $createResponse['assetId'];

    // Verify it exists
    $asset = Asset::find()->id($assetId)->one();
    expect($asset)->toBeInstanceOf(Asset::class);

    // Delete it
    $deleteAsset = Craft::$container->get(DeleteAsset::class);
    $response = $deleteAsset->delete($assetId);

    expect($response)->toHaveKey('_notes');
    expect($response)->toHaveKey('assetId');
    expect($response['assetId'])->toBe($assetId);
    expect($response['title'])->toBe('To Be Deleted');

    // Verify it no longer exists
    $asset = Asset::find()->id($assetId)->one();
    expect($asset)->toBeNull();
});

test('throws exception when creating asset with invalid volume', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: 999999, // Non-existent volume ID
    );
})->throws(\InvalidArgumentException::class, 'Volume with ID 999999 does not exist');

test('throws exception when creating asset with non-existent file', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    $createAsset->create(
        fileUrl: '/path/to/nonexistent/file.txt',
        volumeId: $this->volumeId,
    );
})->throws(\InvalidArgumentException::class);

test('throws exception when updating non-existent asset', function () {
    $updateAsset = Craft::$container->get(UpdateAsset::class);

    $updateAsset->update(
        assetId: 999999,
        title: 'New Title',
    );
})->throws(\InvalidArgumentException::class, 'Asset with ID 999999 not found');

test('throws exception when deleting non-existent asset', function () {
    $deleteAsset = Craft::$container->get(DeleteAsset::class);

    $deleteAsset->delete(999999);
})->throws(\InvalidArgumentException::class, 'Asset with ID 999999 not found');

test('can create asset with optional folder id', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    // Get root folder
    $rootFolder = Craft::$app->getAssets()->getRootFolderByVolumeId($this->volumeId);

    $response = $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
        folderId: $rootFolder->id,
    );

    expect($response)->toHaveKey('assetId');
    expect($response)->toHaveKey('folderId');
    expect($response['folderId'])->toBe($rootFolder->id);

    $this->createdAssetIds[] = $response['assetId'];
});

test('validates folder exists when creating asset', function () {
    $createAsset = Craft::$container->get(CreateAsset::class);

    $createAsset->create(
        fileUrl: $this->testFilePath,
        volumeId: $this->volumeId,
        folderId: 999999, // Non-existent folder
    );
})->throws(\InvalidArgumentException::class, 'Folder with ID 999999 does not exist');
