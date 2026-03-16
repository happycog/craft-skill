<?php

use happycog\craftmcp\tools\GetAddresses;

beforeEach(function () {
    $this->tool = Craft::$container->get(GetAddresses::class);
});

it('lists addresses without filters', function () {
    $user = createTestUserOwner();
    createUserOwnedAddress($user);

    $response = $this->tool->__invoke();

    expect($response)->toHaveKeys(['_notes', 'results']);
    expect($response['results'])->toBeIterable();
});

it('filters addresses by user owner', function () {
    $user = createTestUserOwner();
    $address = createUserOwnedAddress($user);

    $response = $this->tool->__invoke(ownerId: $user->id, ownerType: $user::class);
    $results = $response['results']->values()->all();

    expect($results)->not->toBeEmpty();
    expect(collect($results)->pluck('addressId'))->toContain($address->id);
});

it('filters addresses by field ownership', function () {
    ['entry' => $entry, 'field' => $field] = createTestEntryOwnerWithAddressesField();
    $address = createFieldOwnedAddress($entry, $field);

    $response = $this->tool->__invoke(
        ownerId: $entry->id,
        ownerType: $entry::class,
        fieldId: $field->id,
    );
    $results = $response['results']->values()->all();

    expect($results)->not->toBeEmpty();
    expect(collect($results)->pluck('addressId'))->toContain($address->id);
});

it('filters by country and locality', function () {
    $user = createTestUserOwner();
    $address = createUserOwnedAddress($user);

    $response = $this->tool->__invoke(countryCode: 'US', locality: 'Portland');
    $results = $response['results']->values()->all();

    expect(collect($results)->pluck('addressId'))->toContain($address->id);
});

it('requires ownerId and ownerType together', function () {
    expect(fn() => $this->tool->__invoke(ownerId: 1))
        ->toThrow(\InvalidArgumentException::class, 'ownerId and ownerType must be provided together');
});
