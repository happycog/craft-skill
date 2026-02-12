<?php

use Composer\Semver\Semver;
use happycog\craftmcp\tools\GetFieldTypes;

it('returns available field types', function () {
    $getFieldTypes = Craft::$container->get(GetFieldTypes::class);
    $result = $getFieldTypes->__invoke();

    expect($result)->toBeArray();
    expect($result)->not->toBeEmpty();

    // Check that each field type has required properties
    foreach ($result as $fieldType) {
        $requiredKeys = ['class', 'name', 'description'];
        
        // icon is only available in Craft 5+
        if (Semver::satisfies(Craft::$app->getVersion(), '>=5.0.0')) {
            $requiredKeys[] = 'icon';
        }
        
        expect($fieldType)->toHaveKeys($requiredKeys);
        expect($fieldType['class'])->toBeString();
        expect($fieldType['name'])->toBeString();
        expect($fieldType['description'])->toBeString();
    }
});

it('only returns selectable field types', function () {
    $getFieldTypes = Craft::$container->get(GetFieldTypes::class);
    $result = $getFieldTypes->__invoke();

    // Verify all returned field types are selectable
    foreach ($result as $fieldType) {
        $fieldTypeClass = $fieldType['class'];
        expect($fieldTypeClass::isSelectable())->toBeTrue();
    }
});

it('includes common built-in field types', function () {
    $getFieldTypes = Craft::$container->get(GetFieldTypes::class);
    $result = $getFieldTypes->__invoke();

    $fieldTypeClasses = array_column($result, 'class');

    // Check for some common field types that should be available
    expect($fieldTypeClasses)->toContain('craft\fields\PlainText');
    expect($fieldTypeClasses)->toContain('craft\fields\Number');
    expect($fieldTypeClasses)->toContain('craft\fields\Assets');
});

it('returns field types sorted by name', function () {
    $getFieldTypes = Craft::$container->get(GetFieldTypes::class);
    $result = $getFieldTypes->__invoke();

    $names = array_column($result, 'name');
    $sortedNames = $names;
    sort($sortedNames);

    expect($names)->toBe($sortedNames);
});