<?php

namespace happycog\craftmcp\actions;

use craft\base\ElementInterface;
use craft\elements\Address;
use craft\elements\User;
use craft\fields\Addresses as AddressesField;
use craft\helpers\ElementHelper;

class FormatAddress
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Address $address): array
    {
        $owner = $address->getOwner();
        $field = $address->getField();

        return [
            'addressId' => $address->id,
            'title' => $address->title,
            'fullName' => $address->fullName,
            'firstName' => $address->firstName,
            'lastName' => $address->lastName,
            'countryCode' => $address->countryCode,
            'administrativeArea' => $address->administrativeArea,
            'locality' => $address->locality,
            'dependentLocality' => $address->dependentLocality,
            'postalCode' => $address->postalCode,
            'sortingCode' => $address->sortingCode,
            'addressLine1' => $address->addressLine1,
            'addressLine2' => $address->addressLine2,
            'addressLine3' => $address->addressLine3,
            'organization' => $address->organization,
            'organizationTaxId' => $address->organizationTaxId,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'fieldId' => $address->fieldId,
            'fieldHandle' => $field?->handle,
            'ownerId' => $address->getOwnerId(),
            'primaryOwnerId' => $address->getPrimaryOwnerId(),
            'ownerType' => $owner ? $owner::class : null,
            'ownerTitle' => $this->ownerTitle($owner),
            'dateCreated' => $address->dateCreated?->format('c'),
            'dateUpdated' => $address->dateUpdated?->format('c'),
            'url' => $owner ? ElementHelper::elementEditorUrl($owner) : null,
            'customFields' => $address->getSerializedFieldValues(),
        ];
    }

    private function ownerTitle(?ElementInterface $owner): ?string
    {
        if ($owner === null) {
            return null;
        }

        if ($owner instanceof User) {
            return $owner->friendlyName;
        }

        return $owner->title ?? null;
    }
}
