<?php

use happycog\craftmcp\tools\UpdateAddress;

beforeEach(function () {
    $this->tool = Craft::$container->get(UpdateAddress::class);
});

it('updates a user-owned address', function () {
    $user = createTestUserOwner();
    $address = createUserOwnedAddress($user);

    $response = $this->tool->__invoke(
        addressId: $address->id,
        addressLine1: '456 Updated St',
        locality: 'Seattle',
    );

    expect($response['_notes'])->toBe('The address was successfully updated.');
    expect($response['addressLine1'])->toBe('456 Updated St');
    expect($response['locality'])->toBe('Seattle');
});

it('updates a field-owned address', function () {
    ['entry' => $entry, 'field' => $field] = createTestEntryOwnerWithAddressesField();
    $address = createFieldOwnedAddress($entry, $field);

    $response = $this->tool->__invoke(
        addressId: $address->id,
        title: 'Updated Office',
        postalCode: '94107',
    );

    expect($response['title'])->toBe('Updated Office');
    expect($response['postalCode'])->toBe('94107');
    expect($response['fieldId'])->toBe($field->id);
});

it('throws when address is not found', function () {
    expect(fn() => $this->tool->__invoke(addressId: 99999, locality: 'Nowhere'))
        ->toThrow(\InvalidArgumentException::class, 'Address with ID 99999 not found');
});
