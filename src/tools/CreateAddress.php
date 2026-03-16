<?php

namespace happycog\craftmcp\tools;

use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\Address;
use craft\fields\Addresses as AddressesField;
use craft\helpers\ElementHelper;
use happycog\craftmcp\actions\FormatAddress;
use happycog\craftmcp\actions\ResolveElementOwner;

class CreateAddress
{
    public function __construct(
        protected FormatAddress $formatAddress,
        protected ResolveElementOwner $resolveElementOwner,
    ) {
    }

    /**
     * Create a new Address element for a generic owner.
     *
     * Addresses can belong directly to an element such as a user, or to an `Addresses`
     * custom field on another element. For field-backed addresses, provide either `fieldId`
     * or `fieldHandle` in addition to the owner information.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function __invoke(
        int $ownerId,
        string $ownerType,
        ?int $fieldId = null,
        ?string $fieldHandle = null,
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
        [$owner] = ($this->resolveElementOwner)($ownerId, $ownerType);
        $field = $this->resolveAddressesField($fieldId, $fieldHandle, $owner);

        $address = new Address();
        $address->setOwner($owner);
        $address->setPrimaryOwner($owner);
        $address->siteId = $owner->siteId;
        $address->setScenario(Address::SCENARIO_LIVE);
        $address->fieldId = is_int($field?->id) ? $field->id : null;

        $this->applyAddressAttributes(
            address: $address,
            title: $title,
            fullName: $fullName,
            firstName: $firstName,
            lastName: $lastName,
            countryCode: $countryCode,
            administrativeArea: $administrativeArea,
            locality: $locality,
            dependentLocality: $dependentLocality,
            postalCode: $postalCode,
            sortingCode: $sortingCode,
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            addressLine3: $addressLine3,
            organization: $organization,
            organizationTaxId: $organizationTaxId,
            latitude: $latitude,
            longitude: $longitude,
            fields: $fields,
        );

        throw_unless(
            \Craft::$app->getElements()->saveElement($address),
            'Failed to save address: ' . implode(', ', $address->getFirstErrors()),
        );

        return [
            '_notes' => 'The address was successfully created.',
            ...($this->formatAddress)($address),
        ];
    }

    private function resolveAddressesField(?int $fieldId, ?string $fieldHandle, ElementInterface $owner): ?AddressesField
    {
        if ($fieldId === null && $fieldHandle === null) {
            return null;
        }

        $field = null;
        if ($fieldId !== null) {
            $field = \Craft::$app->getFields()->getFieldById($fieldId);
        } elseif ($fieldHandle !== null) {
            $field = \Craft::$app->getFields()->getFieldByHandle($fieldHandle);
        }

        $fieldIdentifier = $fieldId !== null ? "ID {$fieldId}" : "handle '{$fieldHandle}'";
        throw_unless($field instanceof AddressesField, \InvalidArgumentException::class, "Addresses field with {$fieldIdentifier} not found");

        $resolvedFieldId = $field->id;
        throw_unless(is_int($resolvedFieldId), \RuntimeException::class, 'Addresses field ID is missing.');

        $ownerField = $owner->getFieldLayout()?->getFieldById($resolvedFieldId);
        $ownerIdentifier = $owner::class . '#' . $owner->id;
        throw_unless($ownerField instanceof AddressesField, \InvalidArgumentException::class, "Field {$field->handle} is not attached to owner {$ownerIdentifier}");

        return $field;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function applyAddressAttributes(
        Address $address,
        ?string $title,
        ?string $fullName,
        ?string $firstName,
        ?string $lastName,
        ?string $countryCode,
        ?string $administrativeArea,
        ?string $locality,
        ?string $dependentLocality,
        ?string $postalCode,
        ?string $sortingCode,
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $addressLine3,
        ?string $organization,
        ?string $organizationTaxId,
        ?string $latitude,
        ?string $longitude,
        array $fields,
    ): void {
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

        $address->setAttributes(array_filter($attributes, fn(mixed $value) => $value !== null));

        if ($fields !== []) {
            $address->setFieldValues($fields);
        }
    }
}
