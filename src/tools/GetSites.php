<?php

namespace happycog\craftmcp\tools;

use Craft;

class GetSites
{
    /**
     * Get a list of all available sites in the Craft installation. This is useful for understanding the
     * multi-site structure and discovering valid siteId values for creating and updating drafts.
     *
     * Returns site information including:
     * - id: The site ID (integer)
     * - name: Display name of the site
     * - handle: Machine-readable handle
     * - url: Base URL if configured
     * - primary: Whether this is the primary site
     * - language: Site language code
     *
     * Works for both single-site and multi-site installations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sites = Craft::$app->getSites()->getAllSites();
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        $result = [];
        foreach ($sites as $site) {
            $result[] = [
                'id' => $site->id,
                'name' => $site->name,
                'handle' => $site->handle,
                'url' => $site->getBaseUrl(),
                'primary' => $site->id === $primarySiteId,
                'language' => $site->language,
            ];
        }

        return $result;
    }
}