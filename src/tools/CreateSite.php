<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use happycog\craftmcp\exceptions\ModelSaveException;

class CreateSite
{
    /**
     * Create a new site in Craft CMS. Sites allow you to manage multi-site/multi-language installations
     * with site-specific content and URLs.
     *
     * Each site requires:
     * - A unique name and handle
     * - A base URL for the site
     * - A language code (e.g., 'en-US', 'de-DE')
     * - Optional: Whether this should be the primary site
     *
     * After creating the site always link the user back to the site settings in the Craft control panel
     * so they can review and further configure the site in the context of the Craft UI.
     *
     * @return array<string, mixed>
     */
    public function create(
        /** The display name for the site */
        string $name,

        /** The base URL for the site, e.g., "https://example.com" or "@web" */
        string $baseUrl,

        /** The language code for the site (e.g., 'en-US', 'de-DE', 'fr-FR') */
        string $language,

        /** The site handle (machine-readable name). Auto-generated from name if not provided. */
        ?string $handle = null,

        /** Whether this site should be the primary site. Only one site can be primary. */
        bool $primary = false,

        /** Whether this site should be enabled */
        bool $enabled = true
    ): array {
        throw_unless(trim($name) !== '', 'Site name cannot be empty');
        throw_unless(trim($baseUrl) !== '', 'Base URL cannot be empty');
        throw_unless(trim($language) !== '', 'Language cannot be empty');

        // Auto-generate handle if not provided
        $handle ??= StringHelper::toHandle($name);

        // Create the site
        $site = new Site([
            'name' => $name,
            'handle' => $handle,
            'language' => $language,
            'baseUrl' => $baseUrl,
            'primary' => $primary,
            'enabled' => $enabled,
        ]);

        // Save the site
        $sitesService = Craft::$app->getSites();

        throw_unless($sitesService->saveSite($site), ModelSaveException::class, $site);

        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/sites/' . $site->id);

        return [
            'siteId' => $site->id,
            'name' => $site->name,
            'handle' => $site->handle,
            'language' => $site->language,
            'baseUrl' => $site->getBaseUrl(),
            'primary' => $site->primary,
            'enabled' => $site->enabled,
            'editUrl' => $editUrl,
        ];
    }
}
