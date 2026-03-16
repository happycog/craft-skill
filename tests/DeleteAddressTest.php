<?php

use happycog\craftmcp\tools\DeleteAddress;

beforeEach(function () {
    $this->tool = Craft::$container->get(DeleteAddress::class);
});

it('soft deletes a user-owned address', function () {
    $user = createTestUserOwner();
    $address = createUserOwnedAddress($user);

    $response = $this->tool->__invoke(addressId: $address->id);

    expect($response['_notes'])->toBe('The address was successfully deleted.');
    expect($response['addressId'])->toBe($address->id);
    expect($response['deletedPermanently'])->toBeFalse();
});

it('permanently deletes a field-owned address', function () {
    ['entry' => $entry, 'field' => $field] = createTestEntryOwnerWithAddressesField();
    $address = createFieldOwnedAddress($entry, $field);

    $response = $this->tool->__invoke(addressId: $address->id, permanentlyDelete: true);

    expect($response['addressId'])->toBe($address->id);
    expect($response['fieldId'])->toBe($field->id);
    expect($response['deletedPermanently'])->toBeTrue();
});

it('throws when address is not found', function () {
    expect(fn() => $this->tool->__invoke(addressId: 99999))
        ->toThrow(\InvalidArgumentException::class, 'Address with ID 99999 not found');
});
