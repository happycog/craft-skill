<?php

declare(strict_types=1);

namespace happycog\craftmcp\actions;

use craft\base\FieldInterface;
use craft\fields\Matrix;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\fieldlayoutelements\CustomField as CustomFieldElement;

class FieldFormatter
{
    /**
     * Format fields for a given FieldLayout preserving order, tabs, and required/width context.
     *
     * @return array<int, array<string, mixed>>
     */
    public function formatFieldsForLayout(FieldLayout $layout): array
    {
        $results = [];
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $el) {
                if (!$el instanceof CustomFieldElement) {
                    continue;
                }
                $field = $el->getField();
                if (!$field instanceof FieldInterface) {
                    continue;
                }
                $results[] = $this->formatField($field, $el, $tab);
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
    public function formatField(FieldInterface $field, ?CustomFieldElement $layoutEl = null, ?FieldLayoutTab $tab = null): array
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

        // Nested fields (Matrix)
        if ($field instanceof Matrix) {
            $blockTypes = [];
            foreach ($field->getEntryTypes() as $blockType) {
                $blockLayout = $blockType->getFieldLayout();
                $blockFields = $this->formatFieldsForLayout($blockLayout);
                $blockTypes[] = [
                    'id' => $blockType->id,
                    'handle' => $blockType->handle,
                    'name' => $blockType->name,
                    'fields' => $blockFields,
                ];
            }
            $fieldData['blockTypes'] = $blockTypes;
        }

        return $fieldData;
    }
}
