<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use happycog\craftmcp\exceptions\ModelSaveException;

class CreateSection
{
    /**
     * Create a new section in Craft CMS. Sections define the structural organization of content with different types:
     * - Single: One entry per section (e.g., homepage, about page)
     * - Channel: Multiple entries with flexible structure (e.g., news, blog posts)
     * - Structure: Hierarchical entries with parent-child relationships (e.g., pages with nested structure)
     *
     * Supports multi-site installations with site-specific settings. Entry types must be created separately using the
     * CreateEntryType tool and can be assigned to the section after creation.
     *
     * Returns the section details including control panel URL for further configuration.
     *
     * After creating the section always link the user back to the section settings in the Craft control panel
     * so they can review and further configure the section in the context of the Craft UI.
     *
     * @param 'single'|'channel'|'structure' $type
     * @param array<int> $entryTypeIds
     * @param 'all'|'siteGroup'|'language'|'custom'|'none' $propagationMethod
     * @param 'beginning'|'end' $defaultPlacement
     * @param array<int, array{siteId: int, enabledByDefault?: bool, hasUrls?: bool, uriFormat?: string, template?: string}>|null $siteSettings
     * @return array<string, mixed>
     */
    public function create(
        /** The display name for the section */
        string $name,

        /** Section type: single (one entry), channel (multiple entries), or structure (hierarchical entries) */
        string $type,

        /** Array of entry type IDs to assign to this section. Use CreateEntryType tool to create entry types first. This can also be empty to create a section without any entry types. Although not common this is possible. */
        array $entryTypeIds,

        /** The section handle (machine-readable name). Auto-generated from name if not provided. */
        ?string $handle = null,

        /** Whether to enable entry versioning for this section */
        bool $enableVersioning = true,

        /** How content propagates across sites */
        string $propagationMethod = Section::PROPAGATION_METHOD_ALL,

        /** Maximum hierarchy levels (only for structure sections). Null/0 for unlimited. */
        ?int $maxLevels = null,

        /** Where new entries are placed by default (only for structure sections) */
        string $defaultPlacement = 'end',

        /** Maximum number of authors that can be assigned to entries in this section */
        ?int $maxAuthors = null,

        /**
         * Site-specific settings. If not provided, section will be enabled for all sites with default settings.
         * Each array entry contains:
         * - siteId: Site ID (required)
         * - enabledByDefault: Enable entries by default for this site (optional)
         * - hasUrls: Whether entries have URLs on this site (optional)
         * - uriFormat: URI format pattern, e.g., "blog/{slug}" or "{slug}" (optional)
         * - template: Template path for rendering entries, e.g., "_blog/entry" (optional)
         */
        ?array $siteSettings = null
     ): array {
        throw_unless(in_array($type, [Section::TYPE_SINGLE, Section::TYPE_CHANNEL, Section::TYPE_STRUCTURE]),
                    'Section type must be single, channel, or structure');

        throw_unless(!empty($entryTypeIds), 'At least one entry type ID is required');

        // Validate entry types exist
        $entriesService = Craft::$app->getEntries();
        foreach ($entryTypeIds as $entryTypeId) {
            $entryType = $entriesService->getEntryTypeById($entryTypeId);
            throw_unless($entryType, "Entry type with ID {$entryTypeId} not found");
        }

        // Auto-generate handle if not provided
        $handle ??= StringHelper::toHandle($name);

        // Create the section
        $section = new Section([
            'name' => $name,
            'handle' => $handle,
            'type' => $type,
            'enableVersioning' => $enableVersioning,
            'propagationMethod' => $propagationMethod,
        ]);

        // Set maxAuthors if provided
        if ($maxAuthors !== null) {
            $section->maxAuthors = $maxAuthors;
        }

        // Set entry types
        $entryTypes = [];
        foreach ($entryTypeIds as $entryTypeId) {
            $entryType = $entriesService->getEntryTypeById($entryTypeId);
            throw_unless($entryType, "Entry type with ID {$entryTypeId} not found");
            $entryTypes[] = $entryType;
        }
        $section->setEntryTypes($entryTypes);

        // Set structure-specific properties
        if ($type === Section::TYPE_STRUCTURE) {
            if ($maxLevels !== null && $maxLevels > 0) {
                $section->maxLevels = $maxLevels;
            }

            if ($defaultPlacement !== null) {
                throw_unless(in_array($defaultPlacement, ['beginning', 'end'], true), 'defaultPlacement must be "beginning" or "end"');
                $section->defaultPlacement = $defaultPlacement;
            }
        }

        // Configure site settings
        $siteSettingsObjects = [];

        if ($siteSettings) {
            // Use provided site settings
            foreach ($siteSettings as $siteSettingData) {
                $siteId = $siteSettingData['siteId'];
                throw_unless(is_int($siteId), 'siteId must be an integer');

                $site = Craft::$app->getSites()->getSiteById($siteId);
                throw_unless($site, "Site with ID {$siteId} not found");

                $siteSettingsObjects[$siteId] = new Section_SiteSettings([
                    'siteId' => $siteId,
                    'enabledByDefault' => $siteSettingData['enabledByDefault'] ?? true,
                    'hasUrls' => $siteSettingData['hasUrls'] ?? true,
                    'uriFormat' => $siteSettingData['uriFormat'] ?? ($type === Section::TYPE_SINGLE ? $handle : "{$handle}/{slug}"),
                    'template' => $siteSettingData['template'] ?? null,
                ]);
            }
        } else {
            // Default: enable for all sites with basic settings
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                $defaultUriFormat = $type === Section::TYPE_SINGLE ? $handle : "{$handle}/{slug}";
                $siteSettingsObjects[$site->id] = new Section_SiteSettings([
                    'siteId' => $site->id,
                    'enabledByDefault' => true,
                    'hasUrls' => true,
                    'uriFormat' => $defaultUriFormat,
                    'template' => null,
                ]);
            }
        }

        $section->setSiteSettings($siteSettingsObjects);

        // Save the section
        $sectionsService = Craft::$app->getEntries();

        throw_unless($sectionsService->saveSection($section), ModelSaveException::class, $section);

        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/sections/' . $section->id);

        return [
            'sectionId' => $section->id,
            'name' => $section->name,
            'handle' => $section->handle,
            'type' => $section->type,
            'propagationMethod' => $section->propagationMethod->value,
            'maxLevels' => $section->type === Section::TYPE_STRUCTURE ? ($section->maxLevels ?: null) : null,
            'maxAuthors' => $section->maxAuthors,
            'editUrl' => $editUrl,
        ];
    }
}
