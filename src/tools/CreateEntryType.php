<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\Color;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class CreateEntryType
{
    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'create_entry_type',
        description: <<<'END'
        Create a new entry type in Craft CMS. Entry types define the content schema and can exist
        independently of sections (useful for Matrix fields) or be assigned to sections later.

        Entry types control the structure of content with field layouts and determine whether entries
        have title fields, icon representation, and other content behaviors.

        After creating the entry type always link the user back to the entry type settings in the Craft
        control panel so they can review and further configure the entry type in the context of the Craft UI.
        END
    )]
    public function create(
        #[Schema(type: 'string', description: 'The display name for the entry type')]
        string $name,

        #[Schema(type: 'string', description: 'The entry type handle (machine-readable name). Auto-generated from name if not provided.')]
        ?string $handle = null,

        #[Schema(type: 'boolean', description: 'Whether entries of this type have title fields')]
        bool $hasTitleField = true,

        #[Schema(type: 'string', description: 'How titles are translated: none, site, language, or custom')]
        string $titleTranslationMethod = 'site',

        #[Schema(type: 'string', description: 'Translation key format for custom title translation')]
        ?string $titleTranslationKeyFormat = null,

        #[Schema(type: 'string', description: 'Custom title format pattern (e.g., "{name} - {dateCreated|date}") for controlling entry title display')]
        ?string $titleFormat = null,

        #[Schema(type: 'string', description: 'Icon identifier for the entry type (optional)')]
        ?string $icon = null,

        #[Schema(type: 'string', description: 'Color identifier for the entry type (optional)')]
        ?string $color = null,

        #[Schema(type: 'string', description: 'A short string describing the purpose of the entry type (optional)')]
        ?string $description = null,

        #[Schema(type: 'boolean', description: 'Whether entries of this type show the slug field in the admin UI')]
        bool $showSlugField = true,

        #[Schema(type: 'boolean', description: 'Whether entries of this type show the status field in the admin UI')]
        bool $showStatusField = true,
    ): array
    {
        $entriesService = Craft::$app->getEntries();

        // Generate handle if not provided
        $handle ??= StringHelper::toHandle($name);

        // Map translation method
        $titleTranslationMethodConstant = $this->getTranslationMethodConstant($titleTranslationMethod);

        // Create entry type configuration
        $entryType = new EntryType();
        $entryType->name = $name;
        $entryType->handle = $handle;
        $entryType->description = $description;
        $entryType->hasTitleField = $hasTitleField;
        $entryType->titleTranslationMethod = $titleTranslationMethodConstant;
        $entryType->titleTranslationKeyFormat = $titleTranslationKeyFormat;
        $entryType->titleFormat = $titleFormat;
        $entryType->icon = $icon;
        $entryType->color = $color ? Color::tryFrom($color) : null;
        $entryType->showSlugField = $showSlugField;
        $entryType->showStatusField = $showStatusField;

        // If hasTitleField is true, ensure the field layout includes the title field
        if ($hasTitleField) {
            $fieldLayout = $entryType->getFieldLayout();
            if (!$fieldLayout->isFieldIncluded('title')) {
                $fieldLayout->prependElements([new EntryTitleField()]);
            }
        }

        // If hasTitleField is false, make sure the titleFormat is set, otherwise throw an error
        throw_if(! $hasTitleField && ! empty($titleFormat), \InvalidArgumentException::class, "If 'hasTitleField' is false, 'titleFormat' must be set to define how titles are automatically generated.");

        // Save the entry type
        throw_unless($entriesService->saveEntryType($entryType), ModelSaveException::class, $entryType);

        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/entry-types/' . $entryType->id);

        // Refresh the entry type from database to get the actual saved values
        $entryTypeId = $entryType->id;
        throw_if($entryTypeId === null, \RuntimeException::class, "Entry type was saved but has no ID");

        $savedEntryType = $entriesService->getEntryTypeById($entryTypeId);
        throw_unless($savedEntryType instanceof EntryType, \RuntimeException::class, "Failed to retrieve saved entry type with ID {$entryTypeId}");

        return [
            '_notes' => 'The entry type was successfully created. You can further configure it in the Craft control panel.' . (
                ! $hasTitleField ? ' Because the entry was created without a title field it will not have a field layout. To add fields to this entry type you must first call CreateFieldLayout and then UpdateEntryType with the associated `fieldLayoutId`.' : ''
            ),
            'entryTypeId' => $savedEntryType->id,
            'name' => $savedEntryType->name,
            'handle' => $savedEntryType->handle,
            'description' => $savedEntryType->description,
            'hasTitleField' => $savedEntryType->hasTitleField,
            'titleTranslationMethod' => $savedEntryType->titleTranslationMethod,
            'titleTranslationKeyFormat' => $savedEntryType->titleTranslationKeyFormat,
            'titleFormat' => $savedEntryType->titleFormat,
            'icon' => $savedEntryType->icon,
            'color' => $savedEntryType->color?->value,
            'showSlugField' => $savedEntryType->showSlugField,
            'showStatusField' => $savedEntryType->showStatusField,
            'fieldLayoutId' => $savedEntryType->fieldLayoutId,
            'editUrl' => $editUrl,
        ];
    }

    /**
     * @return 'custom'|'language'|'none'|'site'
     */
    private function getTranslationMethodConstant(string $method): string
    {
        $methodMap = [
            'none' => \craft\base\Field::TRANSLATION_METHOD_NONE,
            'site' => \craft\base\Field::TRANSLATION_METHOD_SITE,
            'language' => \craft\base\Field::TRANSLATION_METHOD_LANGUAGE,
            'custom' => \craft\base\Field::TRANSLATION_METHOD_CUSTOM,
        ];

        throw_unless(isset($methodMap[$method]), \InvalidArgumentException::class, "Invalid translation method '{$method}'. Must be one of: " . implode(', ', array_keys($methodMap)));

        return $methodMap[$method];
    }
}
