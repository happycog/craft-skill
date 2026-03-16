<?php

namespace happycog\craftmcp\tools;

use craft\commerce\Plugin as Commerce;

class GetStores
{
    /**
     * List all Commerce stores with their configuration.
     *
     * Returns all stores configured in Craft Commerce, including checkout settings,
     * currency, and associated sites. Use this to discover available stores and their
     * current configuration before updating store settings with UpdateStore.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $commerce = Commerce::getInstance();
        throw_unless($commerce, 'Craft Commerce is not installed or enabled.');

        $allStores = $commerce->getStores()->getAllStores();

        $stores = [];
        foreach ($allStores as $store) {
            $sites = [];
            /** @var \craft\models\Site $site */
            foreach ($store->getSites() as $site) {
                $sites[] = [
                    'id' => $site->id,
                    'name' => $site->getName(),
                    'handle' => $site->handle,
                ];
            }

            $stores[] = [
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

        return [
            '_notes' => 'Retrieved all Commerce stores.',
            'stores' => $stores,
        ];
    }
}
