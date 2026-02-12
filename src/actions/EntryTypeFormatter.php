<?php

declare(strict_types=1);

namespace happycog\craftmcp\actions;

use Composer\Semver\Semver;
use Craft;
use craft\fields\Matrix;
use craft\helpers\UrlHelper;
use craft\models\EntryType;
use happycog\craftmcp\interfaces\SectionsServiceInterface;
use function happycog\craftmcp\helpers\getMatrixSubTypes;
use function happycog\craftmcp\helpers\service;

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
    public function formatEntryType(EntryType $entryType, bool $includeUsedBy, bool $includeFields = true): array
    {
        // Fields via layout with context (only if requested)
        $fields = null;
        if ($includeFields) {
            $layout = $entryType->getFieldLayout();
            $fields = $this->fieldFormatter->formatFieldsForLayout($layout);
        }

        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/entry-types/' . $entryType->id);

         // Build entry type data
         $data = [
             'id' => $entryType->id,
             'name' => $entryType->name,
             'handle' => $entryType->handle,
             'hasTitleField' => $entryType->hasTitleField,
             'titleTranslationMethod' => $entryType->titleTranslationMethod,
             'titleTranslationKeyFormat' => $entryType->titleTranslationKeyFormat,
             'titleFormat' => $entryType->titleFormat,
             'showStatusField' => $entryType->showStatusField,
             'fieldLayoutId' => $entryType->fieldLayoutId,
             'uid' => $entryType->uid,
             'editUrl' => $editUrl,
         ];

         // Add in Craft 5 fields
        if (Semver::satisfies(Craft::$app->getVersion(), '~5.0')) {
            $data = array_merge($data, [
                'icon' => $entryType->icon,
                'color' => $entryType->color?->value,
                'showSlugField' => $entryType->showSlugField,
            ]);
        }

         // Only include fields if they were requested
         if ($includeFields) {
             $data['fields'] = $fields;
         }

         // Merge optional properties
         return array_merge($data, array_filter([
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
        $sections = service(SectionsServiceInterface::class)->getAllSections();

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
                foreach (getMatrixSubTypes($field) as $blockType) {
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
