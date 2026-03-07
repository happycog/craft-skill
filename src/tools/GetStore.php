<?php

namespace happycog\craftmcp\tools;

use craft\commerce\models\Store;
use craft\commerce\Plugin as Commerce;

class GetStore
{
    /**
     * Get detailed information about a single Commerce store by ID.
     *
     * Returns the store's full configuration including checkout settings, currency,
     * pricing strategies, and associated sites. Use this to inspect a specific store's
     * settings before making changes with UpdateStore.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $storeId,
    ): array {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $store = $commerce->getStores()->getStoreById($storeId);

        throw_unless($store instanceof Store, \InvalidArgumentException::class, "Store with ID {$storeId} not found");

        $sites = [];
        /** @var \craft\models\Site $site */
        foreach ($store->getSites() as $site) {
            $sites[] = [
                'id' => $site->id,
                'name' => $site->getName(),
                'handle' => $site->handle,
            ];
        }

        return [
            '_notes' => 'Retrieved store details.',
            'id' => $store->id,
            'name' => $store->getName(),
            'handle' => $store->handle,
            'primary' => $store->primary,
            'currency' => $store->getCurrency()?->getCode(),
            'autoSetNewCartAddresses' => (bool) $store->getAutoSetNewCartAddresses(),
            'autoSetCartShippingMethodOption' => (bool) $store->getAutoSetCartShippingMethodOption(),
            'autoSetPaymentSource' => (bool) $store->getAutoSetPaymentSource(),
            'allowEmptyCartOnCheckout' => (bool) $store->getAllowEmptyCartOnCheckout(),
            'allowCheckoutWithoutPayment' => (bool) $store->getAllowCheckoutWithoutPayment(),
            'allowPartialPaymentOnCheckout' => (bool) $store->getAllowPartialPaymentOnCheckout(),
            'requireShippingAddressAtCheckout' => (bool) $store->getRequireShippingAddressAtCheckout(),
            'requireBillingAddressAtCheckout' => (bool) $store->getRequireBillingAddressAtCheckout(),
            'requireShippingMethodSelectionAtCheckout' => (bool) $store->getRequireShippingMethodSelectionAtCheckout(),
            'useBillingAddressForTax' => (bool) $store->getUseBillingAddressForTax(),
            'validateOrganizationTaxIdAsVatId' => (bool) $store->getValidateOrganizationTaxIdAsVatId(),
            'orderReferenceFormat' => $store->getOrderReferenceFormat(),
            'freeOrderPaymentStrategy' => $store->getFreeOrderPaymentStrategy(),
            'minimumTotalPriceStrategy' => $store->getMinimumTotalPriceStrategy(),
            'sortOrder' => $store->sortOrder,
            'sites' => $sites,
            'url' => $store->getStoreSettingsUrl(),
        ];
    }
}
