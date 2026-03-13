<?php

namespace happycog\craftmcp\tools;

use craft\elements\Address;
use happycog\craftmcp\actions\FormatAddress;

class UpdateAddress
{
    public function __construct(
        protected FormatAddress $formatAddress,
    ) {
    }

    /**
     * Update an existing Address element.
     *
     * Only provided attributes are changed. Owner and address-field attachment are immutable;
     * create a new address if you need to move one to a different owner or field.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function __invoke(
        int $addressId,
        ?string $title = null,
        ?string $fullName = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $countryCode = null,
        ?string $administrativeArea = null,
        ?string $locality = null,
        ?string $dependentLocality = null,
        ?string $postalCode = null,
        ?string $sortingCode = null,
        ?string $addressLine1 = null,
        ?string $addressLine2 = null,
        ?string $addressLine3 = null,
        ?string $organization = null,
        ?string $organizationTaxId = null,
        ?string $latitude = null,
        ?string $longitude = null,
        array $fields = [],
    ): array {
        $address = \Craft::$app->getElements()->getElementById($addressId, Address::class, null, [
            'siteId' => '*',
        ]);

        throw_unless($address instanceof Address, \InvalidArgumentException::class, "Address with ID {$addressId} not found");

        $attributes = [
            'title' => $title,
            'fullName' => $fullName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'countryCode' => $countryCode,
            'administrativeArea' => $administrativeArea,
            'locality' => $locality,
            'dependentLocality' => $dependentLocality,
            'postalCode' => $postalCode,
            'sortingCode' => $sortingCode,
            'addressLine1' => $addressLine1,
            'addressLine2' => $addressLine2,
            'addressLine3' => $addressLine3,
            'organization' => $organization,
            'organizationTaxId' => $organizationTaxId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        $address->setScenario(Address::SCENARIO_LIVE);
        $address->setAttributes(array_filter($attributes, fn(mixed $value) => $value !== null));

        if ($fields !== []) {
            $address->setFieldValues($fields);
        }

        throw_unless(
            \Craft::$app->getElements()->saveElement($address),
            'Failed to save address: ' . implode(', ', $address->getFirstErrors()),
        );

        return [
            '_notes' => 'The address was successfully updated.',
            ...($this->formatAddress)($address),
        ];
    }
}
