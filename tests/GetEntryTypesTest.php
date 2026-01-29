<?php

declare(strict_types=1);

use happycog\craftmcp\tools\GetEntryTypes;

it('GetEntryTypes getAll returns a flat list of entry types with fields', function () {
    $tool = Craft::$container->get(GetEntryTypes::class);
    $result = $tool->__invoke();

    expect($result)->toBeArray();
    expect(count($result))->toBeGreaterThan(0);

    $first = $result[0];
    expect($first)->toHaveKeys([
        'id', 'name', 'handle', 'hasTitleField', 'fieldLayoutId', 'uid', 'editUrl', 'fields'
    ]);
    expect($first['fields'])->toBeArray();
});

it('GetEntryTypes getAll respects entryTypeIds filter', function () {
    $tool = Craft::$container->get(GetEntryTypes::class);
    $all = $tool->__invoke();

    $firstId = $all[0]['id'] ?? null;
    if ($firstId) {
        $filtered = $tool->__invoke([$firstId]);
        expect($filtered)->toHaveCount(1);
        expect($filtered[0]['id'])->toBe($firstId);
    }
});

it('GetEntryTypes entry type format includes edit URL', function () {
    $tool = Craft::$container->get(GetEntryTypes::class);
    $all = $tool->__invoke();

    $entryType = $all[0];

    // Entry type should have an edit URL
    expect($entryType)->toHaveKey('editUrl');
});

it('GetEntryTypes fields include required layout context', function () {
    $tool = Craft::$container->get(GetEntryTypes::class);
    $all = $tool->__invoke();

    // Find an entry type with fields
    foreach ($all as $et) {
        if (!empty($et['fields'])) {
            $field = $et['fields'][0];
            expect($field)->toHaveKeys(['id','handle','name','type','instructions','required']);
            break;
        }
    }
});
