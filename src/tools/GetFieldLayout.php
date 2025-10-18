<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\base\FieldLayoutElement;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\BaseNativeField;
use craft\fieldlayoutelements\BaseUiElement;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use happycog\craftmcp\exceptions\ModelSaveException;

class GetFieldLayout
{
    /**
     * Get the details of a field layout by its ID, including tabs and all field layout elements
     * (custom fields, native fields like title, and UI elements like headings).
     *
     * This returns the complete field layout structure needed to preserve all elements
     * when updating field layouts.
     *
     * @return array<string, mixed>
     */
    public function get(
        /** The ID of the field layout to retrieve */
        int $fieldLayoutId,
    ): array {
        $fieldsService = Craft::$app->getFields();

        // Get the field layout directly
        $fieldLayout = $fieldsService->getLayoutById($fieldLayoutId);
        \throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");

        $fieldLayoutInfo = [
            'id' => $fieldLayout->id,
            'type' => $fieldLayout->type,
            'tabs' => [],
        ];

        foreach ($fieldLayout->getTabs() as $tab) {
            $tabInfo = [
                'name' => $tab->name,
                'elements' => [],
            ];

            /** @var FieldLayoutElement $element */
            foreach ($tab->getElements() as $element) {
                $elementInfo = [
                    'uid' => $element->uid,
                    'type' => $element::class,
                    'width' => $element->width,
                ];

                // Add element-specific properties based on type
                if ($element instanceof CustomField) {
                    $field = $element->getField();
                    if ($field !== null) {
                        $elementInfo['fieldId'] = $field->id;
                        $elementInfo['fieldName'] = $field->name;
                        $elementInfo['fieldHandle'] = $field->handle;
                        $elementInfo['fieldType'] = $field::class;
                        $elementInfo['required'] = $element->required;
                        $elementInfo['label'] = $element->label;
                        $elementInfo['instructions'] = $element->instructions;
                        $elementInfo['tip'] = $element->tip;
                        $elementInfo['warning'] = $element->warning;
                    }
                } elseif ($element instanceof BaseNativeField) {
                    $elementInfo['attribute'] = $element->attribute;
                    $elementInfo['required'] = $element->required;
                    $elementInfo['label'] = $element->label;
                    $elementInfo['instructions'] = $element->instructions;
                    $elementInfo['tip'] = $element->tip;
                    $elementInfo['warning'] = $element->warning;
                    $elementInfo['mandatory'] = $element->mandatory;
                    $elementInfo['requirable'] = $element->requirable;
                    $elementInfo['translatable'] = $element->translatable;
                } elseif ($element instanceof BaseField) {
                    $elementInfo['required'] = $element->required;
                    $elementInfo['label'] = $element->label;
                    $elementInfo['instructions'] = $element->instructions;
                    $elementInfo['tip'] = $element->tip;
                    $elementInfo['warning'] = $element->warning;
                } elseif ($element instanceof BaseUiElement) {
                    // UI elements have their own specific properties
                    // Common UI element properties would be added here
                    // For now, we include the basic element info
                }

                $tabInfo['elements'][] = $elementInfo;
            }

            $fieldLayoutInfo['tabs'][] = $tabInfo;
        }

        return [
            '_notes' => 'Field layout retrieved with all elements including custom fields, native fields, and UI elements.',
            'fieldLayout' => $fieldLayoutInfo,
        ];
    }
}
