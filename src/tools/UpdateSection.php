<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\PropagationMethod;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class UpdateSection
{

    /**
     * @param array<int>|null $entryTypeIds
     * @param array<string, mixed>|null $siteSettingsData
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'update_section',
        description: <<<'END'
        Update an existing section in Craft CMS. Allows modification of section properties
        including name, handle, site settings, and entry type associations while preserving
        existing entry data where possible.

        Section type changes have restrictions: Single ↔ Channel is possible, but Structure
        changes require careful consideration due to hierarchical data. Entry type associations
        can be updated to add or remove entry types from the section.

        After updating the section always link the user back to the section settings in the Craft
        control panel so they can review the changes in the context of the Craft UI.
        END
    )]
    public function update(
        #[Schema(type: 'integer', description: 'The ID of the section to update')]
        int $sectionId,
        
        #[Schema(type: 'string', description: 'The display name for the section')]
        ?string $name = null,
        
        #[Schema(type: 'string', description: 'The section handle (machine-readable name)')]
        ?string $handle = null,
        
        #[Schema(type: 'string', description: 'Section type: single, channel, or structure. Type changes have restrictions based on existing data.', enum: ['single', 'channel', 'structure'])]
        ?string $type = null,
        
        #[Schema(type: 'array', description: 'Array of entry type IDs to assign to this section. Replaces existing associations.', items: ['type' => 'integer'])]
        ?array $entryTypeIds = null,
        
        #[Schema(type: 'boolean', description: 'Whether to enable entry versioning for this section')]
        ?bool $enableVersioning = null,
        
        #[Schema(type: 'string', description: 'How content propagates across sites', enum: ['all', 'siteGroup', 'language', 'custom', 'none'])]
        ?string $propagationMethod = null,
        
        #[Schema(type: 'integer', description: 'Maximum hierarchy levels (only for structure sections). Null/0 for unlimited.')]
        ?int $maxLevels = null,
        
        #[Schema(type: 'string', description: 'Where new entries are placed by default (only for structure sections)', enum: ['beginning', 'end'])]
        ?string $defaultPlacement = null,
        
        #[Schema(type: 'integer', description: 'Maximum number of authors that can be assigned to entries in this section')]
        ?int $maxAuthors = null,
        
        #[Schema(type: 'array', description: 'Site-specific settings for multi-site installations', items: [
            'type' => 'object',
            'properties' => [
                'siteId' => ['type' => 'integer', 'description' => 'Site ID'],
                'enabledByDefault' => ['type' => 'boolean', 'description' => 'Enable entries by default'],
                'hasUrls' => ['type' => 'boolean', 'description' => 'Whether entries have URLs'],
                'uriFormat' => ['type' => 'string', 'description' => 'URI format pattern'],
                'template' => ['type' => 'string', 'description' => 'Template path for rendering']
            ],
            'required' => ['siteId']
        ])]
        ?array $siteSettingsData = null
    ): array {
        $sectionsService = Craft::$app->getEntries();

        // Get existing section
        $section = $sectionsService->getSectionById($sectionId);
        throw_unless($section, "Section with ID {$sectionId} not found");

        // Update basic properties only if provided
        if ($name !== null) {
            $section->name = $name;
        }

        if ($handle !== null) {
            $section->handle = $handle;
        }

        if ($type !== null) {
            $newType = match ($type) {
                'single' => Section::TYPE_SINGLE,
                'channel' => Section::TYPE_CHANNEL,
                'structure' => Section::TYPE_STRUCTURE,
                default => throw new \InvalidArgumentException("Invalid section type: {$type}")
            };

            // Check for type change restrictions
            $this->validateTypeChange($section, $newType);
            $section->type = $newType;
        }

        if ($enableVersioning !== null) {
            $section->enableVersioning = $enableVersioning;
        }

        if ($maxAuthors !== null) {
            $section->maxAuthors = $maxAuthors;
        }

        if ($propagationMethod !== null) {
            $section->propagationMethod = match ($propagationMethod) {
                'all' => PropagationMethod::All,
                'siteGroup' => PropagationMethod::SiteGroup,
                'language' => PropagationMethod::Language,
                'custom' => PropagationMethod::Custom,
                'none' => PropagationMethod::None,
                default => throw new \InvalidArgumentException("Invalid propagation method: {$propagationMethod}")
            };
        }

        // Structure-specific settings
        if ($section->type === Section::TYPE_STRUCTURE) {
            if ($maxLevels !== null) {
                $section->maxLevels = $maxLevels ?: null;
            }

            if ($defaultPlacement !== null) {
                $section->defaultPlacement = match ($defaultPlacement) {
                    'beginning' => Section::DEFAULT_PLACEMENT_BEGINNING,
                    'end' => Section::DEFAULT_PLACEMENT_END,
                    default => throw new \InvalidArgumentException("Invalid default placement: {$defaultPlacement}")
                };
            }
        }

        // Update site settings if provided
        if ($siteSettingsData !== null) {
            $siteSettings = [];
            foreach ($siteSettingsData as $siteData) {
                assert(is_array($siteData), 'Site data must be an array');
                assert(is_int($siteData['siteId']), 'Site ID must be an integer');

                $siteId = $siteData['siteId'];

                // Validate site exists
                $site = Craft::$app->getSites()->getSiteById($siteId);
                throw_unless($site, "Site with ID {$siteId} not found");

                $settings = new Section_SiteSettings([
                    'sectionId' => $section->id,
                    'siteId' => $siteId,
                    'enabledByDefault' => $siteData['enabledByDefault'] ?? true,
                    'hasUrls' => $siteData['hasUrls'] ?? true,
                    'uriFormat' => $siteData['uriFormat'] ?? $this->generateDefaultUriFormat($section->type, $section->handle),
                    'template' => $siteData['template'] ?? null,
                ]);

                $siteSettings[$siteId] = $settings;
            }

            $section->setSiteSettings($siteSettings);
        }

        // Validate and save section
        throw_unless($sectionsService->saveSection($section), ModelSaveException::class, $section);

        // Update entry type associations if provided
        if ($entryTypeIds !== null) {
            // Type-safe conversion from mixed array to array<int>
            assert(is_array($entryTypeIds), 'Entry type IDs must be an array');
            $validatedEntryTypeIds = [];
            foreach ($entryTypeIds as $id) {
                assert(is_int($id), 'Entry type ID must be an integer');
                $validatedEntryTypeIds[] = $id;
            }
            $this->updateEntryTypeAssociations($section, $validatedEntryTypeIds);
        }

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

    /**
     * @param array<int> $entryTypeIds
     */
    private function updateEntryTypeAssociations(Section $section, array $entryTypeIds): void
    {
        $sectionsService = Craft::$app->getEntries();

        // Validate all entry types exist and collect them
        $entryTypes = [];
        foreach ($entryTypeIds as $entryTypeId) {
            $entryType = $sectionsService->getEntryTypeById($entryTypeId);
            throw_unless($entryType, "Entry type with ID {$entryTypeId} not found");
            $entryTypes[] = $entryType;
        }

        // Set the entry types on the section (this replaces all existing associations)
        $section->setEntryTypes($entryTypes);

        // Save the section to persist the entry type associations
        throw_unless($sectionsService->saveSection($section), ModelSaveException::class, $section);
    }

    private function validateTypeChange(Section $section, string $newType): void
    {
        if ($section->type === $newType) {
            return; // No change
        }

        // Check if section has entries
        $hasEntries = \craft\elements\Entry::find()
            ->sectionId($section->id)
            ->exists();

        if (!$hasEntries) {
            return; // No entries, any change is safe
        }

        // Define allowed type changes for sections with entries
        $allowedChanges = [
            Section::TYPE_SINGLE => [Section::TYPE_CHANNEL],
            Section::TYPE_CHANNEL => [Section::TYPE_SINGLE],
            // Structure changes require manual migration
        ];

        $currentType = $section->type;
        if (!isset($allowedChanges[$currentType]) || !in_array($newType, $allowedChanges[$currentType], true)) {
            throw new \RuntimeException(
                "Cannot change section type from {$currentType} to {$newType} when entries exist. " .
                "Structure sections require manual data migration."
            );
        }
    }

    private function generateDefaultUriFormat(string $sectionType, string $handle): string
    {
        return match ($sectionType) {
            Section::TYPE_SINGLE => $handle,
            Section::TYPE_CHANNEL => "{$handle}/{slug}",
            Section::TYPE_STRUCTURE => "{$handle}/{slug}",
            default => "{$handle}/{slug}"
        };
    }
}
