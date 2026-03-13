<?php

namespace happycog\craftmcp\tools;

use craft\elements\Address;
use happycog\craftmcp\actions\FormatAddress;

class DeleteAddress
{
    public function __construct(
        protected FormatAddress $formatAddress,
    ) {
    }

    /**
     * Delete an Address element.
     *
     * By default this performs Craft's standard soft delete. Set permanentlyDelete to true
     * to remove the address permanently.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $addressId,
        bool $permanentlyDelete = false,
    ): array {
        $address = \Craft::$app->getElements()->getElementById($addressId, Address::class, null, [
            'siteId' => '*',
        ]);

        throw_unless($address instanceof Address, \InvalidArgumentException::class, "Address with ID {$addressId} not found");

        $response = [
            '_notes' => 'The address was successfully deleted.',
            ...($this->formatAddress)($address),
            'deletedPermanently' => $permanentlyDelete,
        ];

        throw_unless(
            \Craft::$app->getElements()->deleteElement($address, $permanentlyDelete),
            "Failed to delete address with ID {$addressId}.",
        );

        return $response;
    }
}
