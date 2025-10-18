<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\enums\Color;
use happycog\craftmcp\actions\EntryTypeFormatter;
use happycog\craftmcp\exceptions\ModelSaveException;

class UpdateEntryType
{
    public function __construct(
        private EntryTypeFormatter $entryTypeFormatter,
    ) {
    }
    /**
     * Update an existing entry type in Craft CMS. Allows modification of entry type properties
     * including name, handle, icon, color, title field settings, and field layout assignment while
     * preserving existing content.
     *
     * Entry type updates will preserve field layouts and any existing entries unless structural
     * changes affect compatibility. Handle changes require uniqueness validation.
     *
     * After updating the entry type always link the user back to the entry type settings in the Craft
     * control panel so they can review the changes in the context of the Craft UI.
     *
     * @return array<string, mixed>
     */
    public function update(
        /** The ID of the entry type to update */
        int $entryTypeId,

        /** The display name for the entry type */
        ?string $name = null,

        /** The entry type handle (machine-readable name) */
        ?string $handle = null,

        /** How titles are translated: none, site, language, or custom */
        ?string $titleTranslationMethod = null,

        /** Translation key format for custom title translation */
        ?string $titleTranslationKeyFormat = null,

        /** Custom title format pattern (e.g., "{name} - {dateCreated|date}") for controlling entry title display */
        ?string $titleFormat = null,

        /** Icon identifier for the entry type */
        ?string $icon = null,

        /** Color identifier for the entry type */
        ?string $color = null,

        /** A short string describing the purpose of the entry type (optional) */
        ?string $description = null,

        /** Whether entries of this type show the slug field in the admin UI */
        ?bool $showSlugField = null,

        /** Whether entries of this type show the status field in the admin UI */
        ?bool $showStatusField = null,

        /** The ID of the field layout to assign to this entry type */
        ?int $fieldLayoutId = null,
    ): array
    {
        $entriesService = Craft::$app->getEntries();

        // Get the existing entry type
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        throw_unless($entryType, "Entry type with ID {$entryTypeId} not found");

        // Update properties if provided
        if ($name !== null) {
            $entryType->name = $name;
        }

        if ($handle !== null) {
            $entryType->handle = $handle;
        }

        if ($description !== null) {
            $entryType->description = $description;
        }

        if ($titleTranslationMethod !== null) {
            $entryType->titleTranslationMethod = $this->getTranslationMethodConstant($titleTranslationMethod);
        }

        if ($titleTranslationKeyFormat !== null) {
            $entryType->titleTranslationKeyFormat = $titleTranslationKeyFormat;
        }

        if ($titleFormat !== null) {
            $entryType->titleFormat = $titleFormat;
        }

        if ($icon !== null) {
            $entryType->icon = $icon;
        }

        if ($color !== null) {
            $entryType->color = Color::tryFrom($color);
        }

        if ($showSlugField !== null) {
            $entryType->showSlugField = $showSlugField;
        }

        if ($showStatusField !== null) {
            $entryType->showStatusField = $showStatusField;
        }

        if ($fieldLayoutId !== null) {
            $fieldsService = Craft::$app->getFields();
            $fieldLayout = $fieldsService->getLayoutById($fieldLayoutId);
            throw_unless($fieldLayout instanceof \craft\models\FieldLayout, "Field layout with ID {$fieldLayoutId} not found");
            $entryType->fieldLayoutId = $fieldLayoutId;
        }

        // Save the updated entry type
        throw_unless($entriesService->saveEntryType($entryType), ModelSaveException::class, $entryType);

        // Use the formatter to return consistent entry type data
        return $this->entryTypeFormatter->formatEntryType($entryType, false);
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

        if (!isset($methodMap[$method])) {
            throw new \InvalidArgumentException("Invalid translation method '{$method}'. Must be one of: " . implode(', ', array_keys($methodMap)));
        }

        return $methodMap[$method];
    }
}
