<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\UrlHelper;
use happycog\craftmcp\exceptions\ModelSaveException;

class UpdateSite
{
    /**
     * Update an existing site in Craft CMS. Allows modification of site properties
     * including name, handle, base URL, language, and primary/enabled status.
     *
     * Only one site can be primary at a time. If you set a site as primary, the
     * previous primary site will automatically be demoted.
     *
     * After updating the site always link the user back to the site settings in the Craft
     * control panel so they can review the changes in the context of the Craft UI.
     *
     * @return array<string, mixed>
     */
    public function update(
        /** The ID of the site to update */
        int $siteId,

        /** The display name for the site */
        ?string $name = null,

        /** The site handle (machine-readable name) */
        ?string $handle = null,

        /** The base URL for the site, e.g., "https://example.com" or "@web" */
        ?string $baseUrl = null,

        /** The language code for the site (e.g., 'en-US', 'de-DE', 'fr-FR') */
        ?string $language = null,

        /** Whether this site should be the primary site. Only one site can be primary. */
        ?bool $primary = null,

        /** Whether this site should be enabled */
        ?bool $enabled = null
    ): array {
        $sitesService = Craft::$app->getSites();

        // Get existing site
        $site = $sitesService->getSiteById($siteId);
        throw_unless($site, "Site with ID {$siteId} not found");

        // Update basic properties only if provided
        if ($name !== null) {
            $site->name = $name;
        }

        if ($handle !== null) {
            $site->handle = $handle;
        }

        if ($baseUrl !== null) {
            $site->baseUrl = $baseUrl;
        }

        if ($language !== null) {
            $site->language = $language;
        }

        if ($primary !== null) {
            $site->primary = $primary;
        }

        if ($enabled !== null) {
            $site->enabled = $enabled;
        }

        // Save the site
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
