<?php

namespace happycog\craftmcp;

use Craft;
use happycog\craftmcp\base\Plugin as BasePlugin;
use happycog\craftmcp\attributes\RegisterListener;
use happycog\craftmcp\web\assets\chat\ChatAsset;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

class Plugin extends BasePlugin
{
    public bool $hasCpSettings = true;

    #[RegisterListener(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES)]
    protected function registerSiteUrlRules(RegisterUrlRulesEvent $event): void
    {
        // API routes for Skills
        $apiPrefix = $this->getSettings()->apiPrefix ?? 'api';

        // Section routes
        $event->rules['POST ' . $apiPrefix . '/sections'] = 'skills/sections/create';
        $event->rules['GET ' . $apiPrefix . '/sections'] = 'skills/sections/list';
        $event->rules['PUT ' . $apiPrefix . '/sections/<id>'] = 'skills/sections/update';
        $event->rules['DELETE ' . $apiPrefix . '/sections/<id>'] = 'skills/sections/delete';

        // Entry Type routes
        $event->rules['POST ' . $apiPrefix . '/entry-types'] = 'skills/entry-types/create';
        $event->rules['GET ' . $apiPrefix . '/entry-types'] = 'skills/entry-types/list';
        $event->rules['PUT ' . $apiPrefix . '/entry-types/<id>'] = 'skills/entry-types/update';
        $event->rules['DELETE ' . $apiPrefix . '/entry-types/<id>'] = 'skills/entry-types/delete';

        // Field routes
        $event->rules['POST ' . $apiPrefix . '/fields'] = 'skills/fields/create';
        $event->rules['GET ' . $apiPrefix . '/fields'] = 'skills/fields/list';
        $event->rules['GET ' . $apiPrefix . '/fields/types'] = 'skills/fields/types';
        $event->rules['PUT ' . $apiPrefix . '/fields/<id>'] = 'skills/fields/update';
        $event->rules['DELETE ' . $apiPrefix . '/fields/<id>'] = 'skills/fields/delete';

        // Entry routes
        $event->rules['POST ' . $apiPrefix . '/entries'] = 'skills/entries/create';
        $event->rules['GET ' . $apiPrefix . '/entries/search'] = 'skills/entries/search';
        $event->rules['GET ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/get';
        $event->rules['PUT ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/update';
        $event->rules['DELETE ' . $apiPrefix . '/entries/<id>'] = 'skills/entries/delete';

        // Draft routes
        $event->rules['POST ' . $apiPrefix . '/drafts'] = 'skills/drafts/create';
        $event->rules['PUT ' . $apiPrefix . '/drafts/<id>'] = 'skills/drafts/update';
        $event->rules['POST ' . $apiPrefix . '/drafts/<id>/apply'] = 'skills/drafts/apply';

        // Field Layout routes
        $event->rules['POST ' . $apiPrefix . '/field-layouts'] = 'skills/field-layouts/create';
        $event->rules['GET ' . $apiPrefix . '/field-layouts'] = 'skills/field-layouts/get';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/tabs'] = 'skills/field-layouts/add-tab';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/fields'] = 'skills/field-layouts/add-field';
        $event->rules['POST ' . $apiPrefix . '/field-layouts/<id>/ui-elements'] = 'skills/field-layouts/add-ui-element';
        $event->rules['DELETE ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'skills/field-layouts/remove-element';
        $event->rules['PUT ' . $apiPrefix . '/field-layouts/<id>/elements'] = 'skills/field-layouts/move-element';

        // Site routes
        $event->rules['GET ' . $apiPrefix . '/sites'] = 'skills/sites/list';

        // Asset routes
        $event->rules['POST ' . $apiPrefix . '/assets'] = 'skills/assets/create';
        $event->rules['PUT ' . $apiPrefix . '/assets/<id>'] = 'skills/assets/update';
        $event->rules['DELETE ' . $apiPrefix . '/assets/<id>'] = 'skills/assets/delete';
        $event->rules['GET ' . $apiPrefix . '/volumes'] = 'skills/assets/volumes';

        // Address routes
        $event->rules['GET ' . $apiPrefix . '/addresses'] = 'skills/addresses/list';
        $event->rules['POST ' . $apiPrefix . '/addresses'] = 'skills/addresses/create';
        $event->rules['GET ' . $apiPrefix . '/addresses/<id>'] = 'skills/addresses/get';
        $event->rules['PUT ' . $apiPrefix . '/addresses/<id>'] = 'skills/addresses/update';
        $event->rules['DELETE ' . $apiPrefix . '/addresses/<id>'] = 'skills/addresses/delete';
        $event->rules['GET ' . $apiPrefix . '/addresses/field-layout'] = 'skills/addresses/field-layout';

        // User routes
        $event->rules['GET ' . $apiPrefix . '/users'] = 'skills/users/list';
        $event->rules['POST ' . $apiPrefix . '/users'] = 'skills/users/create';
        $event->rules['GET ' . $apiPrefix . '/users/permissions'] = 'skills/users/permissions';
        $event->rules['GET ' . $apiPrefix . '/users/<id>'] = 'skills/users/get';
        $event->rules['PUT ' . $apiPrefix . '/users/<id>'] = 'skills/users/update';
        $event->rules['DELETE ' . $apiPrefix . '/users/<id>'] = 'skills/users/delete';
        $event->rules['GET ' . $apiPrefix . '/users/field-layout'] = 'skills/users/field-layout';

        // User group routes
        $event->rules['GET ' . $apiPrefix . '/user-groups'] = 'skills/user-groups/list';
        $event->rules['POST ' . $apiPrefix . '/user-groups'] = 'skills/user-groups/create';
        $event->rules['GET ' . $apiPrefix . '/user-groups/<id>'] = 'skills/user-groups/get';
        $event->rules['PUT ' . $apiPrefix . '/user-groups/<id>'] = 'skills/user-groups/update';
        $event->rules['DELETE ' . $apiPrefix . '/user-groups/<id>'] = 'skills/user-groups/delete';

        // Health check route
        $event->rules['GET ' . $apiPrefix . '/health'] = 'skills/health/index';

        // Commerce: Product routes
        $event->rules['POST ' . $apiPrefix . '/products'] = 'skills/products/create';
        $event->rules['GET ' . $apiPrefix . '/products/search'] = 'skills/products/search';
        $event->rules['GET ' . $apiPrefix . '/products/<id>'] = 'skills/products/get';
        $event->rules['PUT ' . $apiPrefix . '/products/<id>'] = 'skills/products/update';
        $event->rules['DELETE ' . $apiPrefix . '/products/<id>'] = 'skills/products/delete';
        $event->rules['GET ' . $apiPrefix . '/product-types'] = 'skills/products/types';
        $event->rules['POST ' . $apiPrefix . '/product-types'] = 'skills/products/create-type';
        $event->rules['GET ' . $apiPrefix . '/product-types/<id>'] = 'skills/products/get-type';
        $event->rules['PUT ' . $apiPrefix . '/product-types/<id>'] = 'skills/products/update-type';
        $event->rules['DELETE ' . $apiPrefix . '/product-types/<id>'] = 'skills/products/delete-type';

        // Commerce: Variant routes
        $event->rules['POST ' . $apiPrefix . '/variants'] = 'skills/variants/create';
        $event->rules['GET ' . $apiPrefix . '/variants/<id>'] = 'skills/variants/get';
        $event->rules['PUT ' . $apiPrefix . '/variants/<id>'] = 'skills/variants/update';
        $event->rules['DELETE ' . $apiPrefix . '/variants/<id>'] = 'skills/variants/delete';

        // Commerce: Order routes
        $event->rules['GET ' . $apiPrefix . '/orders/search'] = 'skills/orders/search';
        $event->rules['GET ' . $apiPrefix . '/orders/<id>'] = 'skills/orders/get';
        $event->rules['PUT ' . $apiPrefix . '/orders/<id>'] = 'skills/orders/update';
        $event->rules['GET ' . $apiPrefix . '/order-statuses'] = 'skills/orders/statuses';

        // Commerce: Store routes
        $event->rules['GET ' . $apiPrefix . '/stores'] = 'skills/stores/list';
        $event->rules['GET ' . $apiPrefix . '/stores/<id>'] = 'skills/stores/get';
        $event->rules['PUT ' . $apiPrefix . '/stores/<id>'] = 'skills/stores/update';
    }

    protected function settingsHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(ChatAsset::class);

        return Craft::$app->getView()->renderTemplate('skills/settings', [
            'settings' => $this->getSettings(),
        ]);
    }
}
