<?php

declare(strict_types=1);

namespace happycog\craftmcp\actions;

use Craft;
use craft\fields\Matrix;
use craft\helpers\UrlHelper;
use craft\models\EntryType;

class EntryTypeFormatter
{
    public function __construct(
        protected FieldFormatter $fieldFormatter,
    ) {
    }

    /**
     * Format an entry type with control panel edit URL and usage information.
     *
     * @return array<string, mixed>
     */
    public function formatEntryType(EntryType $entryType, bool $includeUsedBy): array
    {
        // Fields via layout with context
        $layout = $entryType->getFieldLayout();
        $fields = $this->fieldFormatter->formatFieldsForLayout($layout);

        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/entry-types/' . $entryType->id);

        // Build entry type data
        return array_merge([
            'id' => $entryType->id,
            'name' => $entryType->name,
            'handle' => $entryType->handle,
            'description' => $entryType->description,
            'hasTitleField' => $entryType->hasTitleField,
            'titleTranslationMethod' => $entryType->titleTranslationMethod,
            'titleTranslationKeyFormat' => $entryType->titleTranslationKeyFormat,
            'titleFormat' => $entryType->titleFormat,
            'icon' => $entryType->icon,
            'color' => $entryType->color?->value,
            'showSlugField' => $entryType->showSlugField,
            'showStatusField' => $entryType->showStatusField,
            'fieldLayoutId' => $entryType->fieldLayoutId,
            'uid' => $entryType->uid,
            'fields' => $fields,
            'editUrl' => $editUrl,
        ], array_filter([
            'usedBy' => $includeUsedBy ? $this->findEntryTypeUsage($entryType) : null,
        ]));
    }

    /**
     * Find all sections and Matrix fields that use this entry type.
     *
     * @return array<string, mixed>
     */
    private function findEntryTypeUsage(EntryType $entryType): array
    {
        $usage = [
            'sections' => [],
            'matrixFields' => [],
        ];

        // Find sections that contain this entry type
        $entriesService = Craft::$app->getEntries();
        $sections = $entriesService->getAllSections();

        foreach ($sections as $section) {
            foreach ($section->getEntryTypes() as $sectionEntryType) {
                if ($sectionEntryType->id === $entryType->id) {
                    $usage['sections'][] = [
                        'id' => $section->id,
                        'name' => $section->name,
                        'handle' => $section->handle,
                        'type' => $section->type,
                    ];
                    break; // Entry type found in this section, move to next section
                }
            }
        }

        // Find Matrix fields that use this entry type as a block type
        $fieldsService = Craft::$app->getFields();
        $allFields = $fieldsService->getAllFields('global');

        foreach ($allFields as $field) {
            // Check if this is a Matrix field
            if ($field instanceof Matrix) {
                foreach ($field->getEntryTypes() as $blockType) {
                    if ($blockType->id === $entryType->id) {
                        $usage['matrixFields'][] = [
                            'id' => $field->id,
                            'name' => $field->name,
                            'handle' => $field->handle,
                            'type' => get_class($field),
                        ];
                        break; // Entry type found in this Matrix field, move to next field
                    }
                }
            }
        }

        return $usage;
    }
}
