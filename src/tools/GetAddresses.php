<?php

namespace happycog\craftmcp\tools;

use craft\elements\Address;
use craft\fields\Addresses as AddressesField;
use Illuminate\Support\Collection;
use happycog\craftmcp\actions\FormatAddress;
use happycog\craftmcp\actions\ResolveElementOwner;

class GetAddresses
{
    public function __construct(
        protected FormatAddress $formatAddress,
        protected ResolveElementOwner $resolveElementOwner,
    ) {
    }

    /**
     * Search and list addresses with optional filtering by owner, field, country, and locality.
     *
     * Because Address is a nested element type, filters are especially useful for finding
     * addresses belonging to a specific user or attached to an `Addresses` custom field
     * on another element.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        ?int $ownerId = null,
        ?string $ownerType = null,
        ?int $fieldId = null,
        ?string $fieldHandle = null,
        ?string $countryCode = null,
        ?string $postalCode = null,
        ?string $locality = null,
        int $limit = 10,
    ): array {
        $query = Address::find()
            ->siteId('*')
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->limit($limit);

        $notes = [];

        if ($ownerId !== null || $ownerType !== null) {
            throw_unless($ownerId !== null && $ownerType !== null, \InvalidArgumentException::class, 'ownerId and ownerType must be provided together');
            [$owner] = ($this->resolveElementOwner)($ownerId, $ownerType);
            $query->owner($owner);
            $notes[] = "owner {$ownerType}#{$ownerId}";
        }

        if ($fieldId !== null || $fieldHandle !== null) {
            $field = $this->resolveAddressesField($fieldId, $fieldHandle);
            $query->fieldId($field->id);
            $notes[] = "field {$field->handle}";
        }

        if ($countryCode !== null) {
            $query->countryCode($countryCode);
            $notes[] = "country {$countryCode}";
        }

        if ($postalCode !== null) {
            $query->postalCode($postalCode);
            $notes[] = "postal code {$postalCode}";
        }

        if ($locality !== null) {
            $query->locality($locality);
            $notes[] = "locality {$locality}";
        }

        $results = $query->all();

        return [
            '_notes' => empty($notes)
                ? 'The following addresses were found.'
                : 'The following addresses were found matching ' . implode(' and ', $notes) . '.',
            'results' => Collection::make($results)->map(fn(Address $address) => ($this->formatAddress)($address)),
        ];
    }

    private function resolveAddressesField(?int $fieldId, ?string $fieldHandle): AddressesField
    {
        $fields = \Craft::$app->getFields();

        if ($fieldId !== null) {
            $field = $fields->getFieldById($fieldId);
            throw_unless($field instanceof AddressesField, \InvalidArgumentException::class, "Addresses field with ID {$fieldId} not found");
            return $field;
        }

        assert($fieldHandle !== null);
        $field = $fields->getFieldByHandle($fieldHandle);
        throw_unless($field instanceof AddressesField, \InvalidArgumentException::class, "Addresses field with handle '{$fieldHandle}' not found");
        return $field;
    }
}
