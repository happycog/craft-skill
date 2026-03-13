<?php

namespace happycog\craftmcp\tools;

use craft\elements\Address;
use happycog\craftmcp\actions\FormatAddress;

class GetAddress
{
    public function __construct(
        protected FormatAddress $formatAddress,
    ) {
    }

    /**
     * Get detailed information about a single Address element by ID.
     *
     * Returns native address attributes, owner information, address-field linkage,
     * and any custom fields defined on the global address field layout.
     *
     * @return array<string, mixed>
     */
    public function __invoke(int $addressId): array
    {
        $address = \Craft::$app->getElements()->getElementById($addressId, Address::class, null, [
            'siteId' => '*',
        ]);

        throw_unless($address instanceof Address, \InvalidArgumentException::class, "Address with ID {$addressId} not found");

        return [
            '_notes' => 'Retrieved address details.',
            ...($this->formatAddress)($address),
        ];
    }
}
