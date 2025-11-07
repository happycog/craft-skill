<?php

namespace happycog\craftmcp;

use Craft;
use happycog\craftmcp\base\Plugin as BasePlugin;
use happycog\craftmcp\attributes\RegisterListener;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

class Plugin extends BasePlugin
{
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

        // Health check route
        $event->rules['GET ' . $apiPrefix . '/health'] = 'skills/health/index';
    }
}
