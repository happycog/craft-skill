<?php

declare(strict_types=1);

namespace happycog\craftmcp\actions;

use Craft;
use craft\base\FieldInterface;
use craft\fields\Matrix;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\fieldlayoutelements\CustomField as CustomFieldElement;

class FieldFormatter
{
    /**
     * Static cache of all fields indexed by UID for performance.
     *
     * @var array<string, FieldInterface>|null
     */
    private static ?array $fieldCache = null;

    /**
     * Initialize the static field cache by loading all fields once.
     */
    private function ensureFieldCache(): void
    {
        if (self::$fieldCache !== null) {
            return;
        }

        self::$fieldCache = [];
        $allFields = Craft::$app->getFields()->getAllFields('global');

        foreach ($allFields as $field) {
            if ($field->uid !== null) {
                self::$fieldCache[$field->uid] = $field;
            }
        }
    }

    /**
     * Get a field by UID from the static cache.
     */
    private function getFieldByUid(string $fieldUid): ?FieldInterface
    {
        $this->ensureFieldCache();
        return self::$fieldCache[$fieldUid] ?? null;
    }

    /**
     * Format fields for a given FieldLayout preserving order, tabs, and required/width context.
     *
     * @return array<int, array<string, mixed>>
     */
    public function formatFieldsForLayout(FieldLayout $layout, int $depth = 0): array
    {
        $results = [];
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $el) {
                if (!$el instanceof CustomFieldElement) {
                    continue;
                }

                // Get field UID without hitting the database
                $fieldUid = $el->getFieldUid();
                if ($fieldUid === null) {
                    continue;
                }

                // Retrieve field from static cache (no database query)
                $field = $this->getFieldByUid($fieldUid);
                if (!$field instanceof FieldInterface) {
                    continue;
                }

                $results[] = $this->formatField($field, $el, $tab, $depth);
            }
        }
        return $results;
    }

    /**
     * Format a single field. If a CustomFieldElement and tab are provided, include layout context.
     *
     * @param CustomFieldElement|null $layoutEl
     * @param FieldLayoutTab|null $tab
     * @return array<string, mixed>
     */
    public function formatField(FieldInterface $field, ?CustomFieldElement $layoutEl = null, ?FieldLayoutTab $tab = null, int $depth = 0): array
    {
        $fieldData = [
            'id' => $field->id,
            'handle' => $field->handle,
            'name' => $field->name,
            'type' => get_class($field),
            'instructions' => $field->instructions,
            // layout context
            'required' => $layoutEl ? (bool)$layoutEl->required : (bool)($field->required ?? false),
            'width' => $layoutEl ? ($layoutEl->width ?? null) : null,
            'tab' => $tab ? $tab->name : null,
        ];

        // Nested fields (Matrix) - but only up to 3 levels deep
        if ($field instanceof Matrix) {
            if ($depth >= 3) {
                $fieldData['blockTypes'] = 'Maximum nesting depth reached';
            } else {
                $blockTypes = [];
                foreach ($field->getEntryTypes() as $blockType) {
                    $blockLayout = $blockType->getFieldLayout();
                    $blockFields = $this->formatFieldsForLayout($blockLayout, $depth + 1);
                    $blockTypes[] = [
                        'id' => $blockType->id,
                        'handle' => $blockType->handle,
                        'name' => $blockType->name,
                        'fields' => $blockFields,
                    ];
                }
                $fieldData['blockTypes'] = $blockTypes;
            }
        }

        return $fieldData;
    }
}
