<?php

use happycog\craftmcp\tools\GetAddress;

beforeEach(function () {
    $this->tool = Craft::$container->get(GetAddress::class);
});

it('gets a user-owned address', function () {
    $user = createTestUserOwner();
    $address = createUserOwnedAddress($user);

    $response = $this->tool->__invoke(addressId: $address->id);

    expect($response)->toHaveKeys([
        '_notes', 'addressId', 'title', 'fullName', 'countryCode', 'addressLine1',
        'locality', 'administrativeArea', 'postalCode', 'fieldId', 'fieldHandle',
        'ownerId', 'primaryOwnerId', 'ownerType', 'ownerTitle', 'url', 'customFields',
    ]);
    expect($response['_notes'])->toBe('Retrieved address details.');
    expect($response['addressId'])->toBe($address->id);
    expect($response['ownerId'])->toBe($user->id);
    expect($response['ownerType'])->toBe($user::class);
    expect($response['fieldId'])->toBeNull();
});

it('gets a field-owned address', function () {
    ['entry' => $entry, 'field' => $field] = createTestEntryOwnerWithAddressesField();
    $address = createFieldOwnedAddress($entry, $field);

    $response = $this->tool->__invoke(addressId: $address->id);

    expect($response['addressId'])->toBe($address->id);
    expect($response['ownerId'])->toBe($entry->id);
    expect($response['ownerType'])->toBe($entry::class);
    expect($response['fieldId'])->toBe($field->id);
    expect($response['fieldHandle'])->toBe($field->handle);
});

it('throws when address is not found', function () {
    expect(fn() => $this->tool->__invoke(addressId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Address with ID 99999 not found');
});
