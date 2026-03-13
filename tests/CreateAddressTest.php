<?php

use happycog\craftmcp\tools\CreateAddress;

beforeEach(function () {
    $this->tool = Craft::$container->get(CreateAddress::class);
});

it('creates a user-owned address', function () {
    $user = createTestUserOwner();

    $response = $this->tool->__invoke(
        ownerId: $user->id,
        ownerType: $user::class,
        title: 'Home',
        countryCode: 'US',
        addressLine1: '123 Main St',
        locality: 'Portland',
        administrativeArea: 'OR',
        postalCode: '97205',
    );

    expect($response['_notes'])->toBe('The address was successfully created.');
    expect($response['addressId'])->toBeInt();
    expect($response['ownerId'])->toBe($user->id);
    expect($response['fieldId'])->toBeNull();
});

it('creates an address attached to an addresses field on an entry', function () {
    ['entry' => $entry, 'field' => $field] = createTestEntryOwnerWithAddressesField();

    $response = $this->tool->__invoke(
        ownerId: $entry->id,
        ownerType: $entry::class,
        fieldId: $field->id,
        title: 'Office',
        countryCode: 'US',
        addressLine1: '500 Market St',
        locality: 'San Francisco',
        administrativeArea: 'CA',
        postalCode: '94105',
    );

    expect($response['addressId'])->toBeInt();
    expect($response['ownerId'])->toBe($entry->id);
    expect($response['fieldId'])->toBe($field->id);
    expect($response['fieldHandle'])->toBe($field->handle);
});

it('throws when owner is not found', function () {
    expect(fn() => $this->tool->__invoke(
        ownerId: 99999,
        ownerType: \craft\elements\User::class,
        countryCode: 'US',
    ))->toThrow(\InvalidArgumentException::class, 'Owner craft\\elements\\User with ID 99999 not found');
});

it('throws when addresses field is not attached to owner', function () {
    $user = createTestUserOwner();
    ['field' => $field] = createTestEntryOwnerWithAddressesField();
    $expectedMessage = "Field {$field->handle} is not attached to owner " . $user::class . "#{$user->id}";

    expect(fn() => $this->tool->__invoke(
        ownerId: $user->id,
        ownerType: $user::class,
        fieldId: $field->id,
        countryCode: 'US',
    ))->toThrow(\InvalidArgumentException::class, $expectedMessage);
});
