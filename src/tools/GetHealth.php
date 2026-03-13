<?php

namespace happycog\craftmcp\tools;

use Craft;

class GetHealth
{
    /**
     * Get health status of the plugin and Craft installation.
     *
     * Returns basic information indicating the plugin is installed and working,
     * along with Craft CMS version and site information.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();

        return [
            'status' => 'ok',
            'plugin' => [
                'name' => 'Craft Skill',
                'version' => Craft::$app->getPlugins()->getPlugin('craft-skill')?->getVersion() ?? 'unknown',
                'installed' => true,
            ],
            'craft' => [
                'version' => Craft::$app->getVersion(),
                'edition' => Craft::$app->getEditionName(),
            ],
            'site' => [
                'name' => $primarySite->name,
                'baseUrl' => $primarySite->getBaseUrl() ?? '',
            ],
        ];
    }
}
