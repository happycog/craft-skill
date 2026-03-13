<?php

use craft\elements\Address;
use happycog\craftmcp\tools\GetAddressFieldLayout;

beforeEach(function () {
    $this->tool = Craft::$container->get(GetAddressFieldLayout::class);
});

it('returns the global address field layout', function () {
    $response = $this->tool->__invoke();

    expect($response)->toHaveKeys(['_notes', 'fieldLayout', 'settingsUrl', 'elementType']);
    expect($response['_notes'])->toBe('Retrieved the global address field layout.');
    expect($response['elementType'])->toBe(Address::class);
    expect($response['fieldLayout']['id'])->toBe(GetAddressFieldLayout::PLACEHOLDER_ID);
    expect($response['fieldLayout']['type'])->toBe(Address::class);
    expect($response['fieldLayout']['tabs'])->toBeArray();
    expect($response['settingsUrl'])->toContain('/settings/addresses');
});
