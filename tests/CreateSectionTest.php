<?php

use Composer\Semver\Semver;
use craft\models\Section;
use happycog\craftmcp\tools\CreateSection;
use happycog\craftmcp\tools\CreateEntryType;

beforeEach(function () {
    $this->createdEntryTypeIds = [];

    // Helper to create entry type for testing
    $createEntryType = function (string $name, string $handle = null) {
        $createEntryType = Craft::$container->get(CreateEntryType::class);

        $result = $createEntryType->__invoke(
            name: $name,
            handle: $handle
        );

        $this->createdEntryTypeIds[] = $result['entryTypeId'];

        return $result;
    };

    $this->createSection = function (string $name, string $type, ?array $entryTypeIds = [], array $options = []) use ($createEntryType) {
        if (Semver::satisfies(Craft::$app->getVersion(), '>=5.0.0')) {
            $entryTypeIds = [$createEntryType('Default', 'default')['entryTypeId']];
        }
        else {
            $entryTypeIds = null;
        }

        $createSection = Craft::$container->get(CreateSection::class);

        $result = $createSection->__invoke(
            name: $name,
            type: $type,
            entryTypeIds: $entryTypeIds,
            handle: $options['handle'] ?? null,
            enableVersioning: $options['enableVersioning'] ?? true,
            propagationMethod: $options['propagationMethod'] ?? Section::PROPAGATION_METHOD_ALL,
            maxLevels: $options['maxLevels'] ?? null,
            defaultPlacement: $options['defaultPlacement'] ?? Section::DEFAULT_PLACEMENT_END,
            maxAuthors: $options['maxAuthors'] ?? null,
            siteSettings: $options['siteSettings'] ?? null
        );

        return $result;
    };
});

test('creates channel section with default settings', function () {
    $result = ($this->createSection)('Test News', 'channel');

    expect($result['name'])->toBe('Test News')
        ->and($result['handle'])->toBe('testNews')
        ->and($result['type'])->toBe('channel')
        ->and($result['sectionId'])->toBeInt()
        ->and($result['editUrl'])->toContain('/settings/sections/');

    if (Semver::satisfies(Craft::$app->getVersion(), '>=5.0.0')) {
        expect($result['propagationMethod'])->toBe('all');
    }
});

test('creates single section with custom handle', function () {
    $result = ($this->createSection)('Homepage', 'single', null, [
        'handle' => 'home'
    ]);

    expect($result['name'])->toBe('Homepage')
        ->and($result['handle'])->toBe('home')
        ->and($result['type'])->toBe('single');
});

test('creates structure section with hierarchy settings', function () {
    $result = ($this->createSection)('Site Pages', 'structure', null, [
        'maxLevels' => 3,
        'defaultPlacement' => 'beginning'
    ]);

    expect($result['name'])->toBe('Site Pages')
        ->and($result['type'])->toBe('structure')
        ->and($result['maxLevels'])->toBe(3);
});

test('creates section with custom propagation method', function () {
    $result = ($this->createSection)('Multi Site Content', 'channel', null, [
        'propagationMethod' => 'siteGroup',
        'enableVersioning' => false
    ]);

    expect($result['name'])->toBe('Multi Site Content')
        ->and($result['propagationMethod'])->toBe('siteGroup');
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'));

test('creates section with site-specific settings', function () {
    // Get the primary site for testing
    $primarySite = Craft::$app->getSites()->getPrimarySite();

    $result = ($this->createSection)('Custom Site Section', 'channel', [
        'siteSettings' => [
            [
                'siteId' => $primarySite->id,
                'enabledByDefault' => true,
                'hasUrls' => true,
                'uriFormat' => 'custom/{slug}',
                'template' => 'custom/_entry'
            ]
        ]
    ]);

    expect($result['name'])->toBe('Custom Site Section')
        ->and($result['type'])->toBe('channel');
});

test('fails when section name is missing', function () {
    $tool = Craft::$container->get(CreateSection::class);

    expect(fn() => $tool->__invoke('', 'channel'))
        ->toThrow(\happycog\craftmcp\exceptions\ModelSaveException::class);
});

test('fails when section type is invalid', function () {
    $tool = new CreateSection();

    expect(fn() => $tool->__invoke('Test Section', 'invalid'))
        ->toThrow(RuntimeException::class, 'Section type must be single, channel, or structure');
});

test('fails when site id is invalid in site settings', function () {
    $tool = new CreateSection();

    expect(fn() => $tool->__invoke('Test Section', 'channel', siteSettings: [
        [
            'siteId' => 99999, // Non-existent site ID
            'enabledByDefault' => true
        ]
    ]))->toThrow(RuntimeException::class, 'Site with ID 99999 not found');
});

test('includes control panel URL in response', function () {
    $result = ($this->createSection)('Control Panel Test', 'channel');

    expect($result['editUrl'])->toContain('/settings/sections/')
        ->and($result['editUrl'])->toContain((string)$result['sectionId']);
});

test('structure section without max levels shows null', function () {
    $result = ($this->createSection)('Unlimited Structure', 'structure');

    expect($result['maxLevels'])->toBeNull()
        ->and($result['type'])->toBe('structure');
});

test('handles duplicate section handle gracefully', function () {
    // First create a section
    ($this->createSection)('Duplicate Test', 'channel', ['handle' => 'duplicateTest']);

    // Try to create another with the same handle
    $tool = new CreateSection();

    expect(fn() => $tool->__invoke('Another Test', 'channel', handle: 'duplicateTest'))
        ->toThrow(\happycog\craftmcp\exceptions\ModelSaveException::class);
});

test('auto-generates handle from name', function () {
    $result = ($this->createSection)('Complex Entry Type Name With Characters!@#', 'channel');

    expect($result['handle'])->toBe('complexEntryTypeNameWithCharacters')
        ->and($result['name'])->toBe('Complex Entry Type Name With Characters!@#');
});

test('creates structure section with unlimited levels', function () {
    $result = ($this->createSection)('Unlimited Pages', 'structure', [
        'maxLevels' => 0 // 0 means unlimited
    ]);

    expect($result['type'])->toBe('structure')
        ->and($result['maxLevels'])->toBeNull(); // Should be null for unlimited
});

test('creates section with maxAuthors setting', function () {
    $result = ($this->createSection)('Multi Author Section', 'channel', null, [
        'maxAuthors' => 3
    ]);

    expect($result['name'])->toBe('Multi Author Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(3);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'));

test('creates section without maxAuthors (uses Craft default)', function () {
    $result = ($this->createSection)('Standard Section', 'channel');

    expect($result['name'])->toBe('Standard Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(1); // Craft CMS default is 1
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'));

test('creates section with maxAuthors set to 1', function () {
    $result = ($this->createSection)('Single Author Section', 'channel', null, [
        'maxAuthors' => 1
    ]);

    expect($result['name'])->toBe('Single Author Section')
        ->and($result['type'])->toBe('channel')
        ->and($result['maxAuthors'])->toBe(1);
})->skip(fn () => Semver::satisfies(Craft::$app->getVersion(), '<5.0.0'));
