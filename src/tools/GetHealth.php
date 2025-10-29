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
    public function get(): array
    {
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
                'name' => Craft::$app->getSites()->getPrimarySite()->name,
                'baseUrl' => Craft::$app->getSites()->getPrimarySite()->getBaseUrl(),
            ],
        ];
    }
}
