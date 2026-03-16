<?php

use craft\elements\Address;
use craft\elements\Entry;
use craft\elements\User;
use craft\fields\Addresses as AddressesField;
use markhuot\craftpest\factories\Entry as EntryFactory;
use markhuot\craftpest\factories\Field as FieldFactory;
use markhuot\craftpest\factories\Section as SectionFactory;
use markhuot\craftpest\factories\User as UserFactory;

function createTestUserOwner(): User
{
    $existingUser = User::find()
        ->status(null)
        ->site('*')
        ->one();

    if ($existingUser instanceof User) {
        return $existingUser;
    }

    /** @var User $user */
    $user = UserFactory::factory()->make();
    $user->admin = true;
    $user->active = true;
    $user->pending = false;
    $user->newPassword = 'Password123!';
    $user->setScenario(User::SCENARIO_REGISTRATION);

    $saved = Craft::$app->getElements()->saveElement($user, false);
    if (!$saved) {
        throw new RuntimeException(implode(' ', $user->getErrorSummary(false)) ?: 'Failed to create test user owner.');
    }

    expect($user->id)->toBeInt();

    return $user;
}

/**
 * @return array{entry: Entry, field: AddressesField}
 */
function createTestEntryOwnerWithAddressesField(): array
{
    /** @var AddressesField $field */
    $field = FieldFactory::factory()
        ->type(AddressesField::class)
        ->name('Address Book')
        ->handle('addressBook')
        ->create();

    $section = SectionFactory::factory()->fields($field)->create();

    /** @var Entry $entry */
    $entry = EntryFactory::factory()
        ->section($section)
        ->type($section->getEntryTypes()[0])
        ->create();

    return [
        'entry' => $entry,
        'field' => $field,
    ];
}

function createUserOwnedAddress(User $user): Address
{
    $address = new Address();
    $address->setOwner($user);
    $address->setPrimaryOwner($user);
    $address->siteId = $user->siteId;
    $address->setScenario(Address::SCENARIO_LIVE);
    $address->countryCode = 'US';
    $address->title = 'Home';
    $address->fullName = 'Test User';
    $address->addressLine1 = '123 Main St';
    $address->locality = 'Portland';
    $address->administrativeArea = 'OR';
    $address->postalCode = '97205';

    $saved = Craft::$app->getElements()->saveElement($address);
    expect($saved)->toBeTrue();

    return $address;
}

function createFieldOwnedAddress(Entry $entry, AddressesField $field): Address
{
    $address = new Address();
    $address->fieldId = $field->id;
    $address->setOwner($entry);
    $address->setPrimaryOwner($entry);
    $address->siteId = $entry->siteId;
    $address->setScenario(Address::SCENARIO_LIVE);
    $address->countryCode = 'US';
    $address->title = 'Office';
    $address->fullName = 'Entry Owner';
    $address->addressLine1 = '500 Market St';
    $address->locality = 'San Francisco';
    $address->administrativeArea = 'CA';
    $address->postalCode = '94105';

    $saved = Craft::$app->getElements()->saveElement($address);
    expect($saved)->toBeTrue();

    return $address;
}
