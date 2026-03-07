<?php

namespace happycog\craftmcp\tools;

use craft\commerce\models\Store;
use craft\commerce\Plugin as Commerce;

class UpdateStore
{
    /**
     * Update a Commerce store's configuration settings.
     *
     * Updates store checkout behavior, pricing strategies, and address requirements.
     * Only the provided settings are updated; all others remain unchanged.
     *
     * Note: Currency cannot be changed after orders have been placed in the store.
     *
     * After updating, link the user to the store settings in the Craft control panel
     * so they can review changes.
     *
     * @param 'complete'|'process' $freeOrderPaymentStrategy
     * @param 'default'|'zero'|'shipping' $minimumTotalPriceStrategy
     * @return array<string, mixed>
     */
    public function __invoke(
        int $storeId,

        /** Store display name. */
        ?string $name = null,

        /** Store currency code (e.g. USD, EUR). Cannot be changed after orders are placed. */
        ?string $currency = null,

        /** Whether to auto-set the user's primary addresses on new carts. */
        ?bool $autoSetNewCartAddresses = null,

        /** Whether to auto-set the first available shipping method on carts. */
        ?bool $autoSetCartShippingMethodOption = null,

        /** Whether to auto-set the user's primary payment source on new carts. */
        ?bool $autoSetPaymentSource = null,

        /** Whether carts are allowed to be empty on checkout. */
        ?bool $allowEmptyCartOnCheckout = null,

        /** Whether orders can be completed without a payment. */
        ?bool $allowCheckoutWithoutPayment = null,

        /** Whether partial payments are allowed from the front end. */
        ?bool $allowPartialPaymentOnCheckout = null,

        /** Whether a shipping address is required before payment. */
        ?bool $requireShippingAddressAtCheckout = null,

        /** Whether a billing address is required before payment. */
        ?bool $requireBillingAddressAtCheckout = null,

        /** Whether shipping method selection is required before payment. */
        ?bool $requireShippingMethodSelectionAtCheckout = null,

        /** Whether to use the billing address (instead of shipping) for tax calculations. */
        ?bool $useBillingAddressForTax = null,

        /** Whether to validate organizationTaxId as a VAT ID. */
        ?bool $validateOrganizationTaxIdAsVatId = null,

        /** Order reference number format template (e.g. "{{number[:7]}}"). */
        ?string $orderReferenceFormat = null,

        /** How free orders are handled: "complete" (immediately) or "process" (via gateway). */
        ?string $freeOrderPaymentStrategy = null,

        /** Minimum total price strategy: "default", "zero", or "shipping". */
        ?string $minimumTotalPriceStrategy = null,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $store = $commerce->getStores()->getStoreById($storeId);

        throw_unless($store instanceof Store, \InvalidArgumentException::class, "Store with ID {$storeId} not found");

        if ($name !== null) {
            $store->setName($name);
        }
        if ($currency !== null) {
            $store->setCurrency($currency);
        }
        if ($autoSetNewCartAddresses !== null) {
            $store->setAutoSetNewCartAddresses($autoSetNewCartAddresses);
        }
        if ($autoSetCartShippingMethodOption !== null) {
            $store->setAutoSetCartShippingMethodOption($autoSetCartShippingMethodOption);
        }
        if ($autoSetPaymentSource !== null) {
            $store->setAutoSetPaymentSource($autoSetPaymentSource);
        }
        if ($allowEmptyCartOnCheckout !== null) {
            $store->setAllowEmptyCartOnCheckout($allowEmptyCartOnCheckout);
        }
        if ($allowCheckoutWithoutPayment !== null) {
            $store->setAllowCheckoutWithoutPayment($allowCheckoutWithoutPayment);
        }
        if ($allowPartialPaymentOnCheckout !== null) {
            $store->setAllowPartialPaymentOnCheckout($allowPartialPaymentOnCheckout);
        }
        if ($requireShippingAddressAtCheckout !== null) {
            $store->setRequireShippingAddressAtCheckout($requireShippingAddressAtCheckout);
        }
        if ($requireBillingAddressAtCheckout !== null) {
            $store->setRequireBillingAddressAtCheckout($requireBillingAddressAtCheckout);
        }
        if ($requireShippingMethodSelectionAtCheckout !== null) {
            $store->setRequireShippingMethodSelectionAtCheckout($requireShippingMethodSelectionAtCheckout);
        }
        if ($useBillingAddressForTax !== null) {
            $store->setUseBillingAddressForTax($useBillingAddressForTax);
        }
        if ($validateOrganizationTaxIdAsVatId !== null) {
            $store->setValidateOrganizationTaxIdAsVatId($validateOrganizationTaxIdAsVatId);
        }
        if ($orderReferenceFormat !== null) {
            $store->setOrderReferenceFormat($orderReferenceFormat);
        }
        if ($freeOrderPaymentStrategy !== null) {
            $store->setFreeOrderPaymentStrategy($freeOrderPaymentStrategy);
        }
        if ($minimumTotalPriceStrategy !== null) {
            $store->setMinimumTotalPriceStrategy($minimumTotalPriceStrategy);
        }

        throw_unless(
            $commerce->getStores()->saveStore($store),
            "Failed to save store: " . implode(', ', $store->getFirstErrors()),
        );

        return [
            '_notes' => 'The store was successfully updated.',
            'id' => $store->id,
            'name' => $store->getName(),
            'handle' => $store->handle,
            'primary' => $store->primary,
            'currency' => $store->getCurrency()?->getCode(),
            'url' => $store->getStoreSettingsUrl(),
        ];
    }
}
