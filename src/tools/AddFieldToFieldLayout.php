<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\base\FieldInterface;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

class AddFieldToFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
    ) {
    }

    /**
     * Add a custom field to a field layout at a specific position within a tab.
     *
     * The target tab must already exist - use add_tab_to_field_layout to create tabs first.
     * Position can be before/after a specific element UID, or prepend/append to the tab's elements.
     *
     * @param array<string, mixed> $position
     * @return array<string, mixed>
     */
    public function add(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /** The ID of the custom field to add */
        int $fieldId,

        /** Name of tab to add field to (must exist) */
        string $tabName,

        /**
         * Positioning configuration:
         * - type: 'before', 'after', 'prepend', or 'append' (required)
         * - elementUid: UID of existing element for 'before' or 'after' positioning
         */
        array $position,

        /** Field width percentage (1-100) */
        ?int $width = null,

        /** Whether field is required */
        ?bool $required = null,

        /** Custom field label override */
        ?string $label = null,

        /** Custom field instructions override */
        ?string $instructions = null,

        /** Field tip text */
        ?string $tip = null,

        /** Field warning text */
        ?string $warning = null,
    ): array {
        $fieldLayout = $this->fieldsService->getLayoutById($fieldLayoutId);
        throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");

        $field = $this->fieldsService->getFieldById($fieldId);
        throw_unless($field instanceof FieldInterface, "Field with ID {$fieldId} not found");

        $positionType = $position['type'] ?? null;
        throw_unless(
            in_array($positionType, ['before', 'after', 'prepend', 'append'], true),
            "Position type must be one of: 'before', 'after', 'prepend', 'append'"
        );

        if (in_array($positionType, ['before', 'after'], true)) {
            throw_unless(
                isset($position['elementUid']) && is_string($position['elementUid']),
                "elementUid is required for 'before' and 'after' positioning"
            );
        }

        $targetTab = null;
        foreach ($fieldLayout->getTabs() as $tab) {
            if ($tab->name === $tabName) {
                $targetTab = $tab;
                break;
            }
        }
        throw_unless($targetTab !== null, "Tab with name '{$tabName}' not found. Create the tab first using add_tab_to_field_layout");

        $newElement = new CustomField($field);
        $width !== null && $newElement->width = $width;
        $required !== null && $newElement->required = $required;
        $label !== null && $newElement->label = $label;
        $instructions !== null && $newElement->instructions = $instructions;
        $tip !== null && $newElement->tip = $tip;
        $warning !== null && $newElement->warning = $warning;

        $existingElements = $targetTab->getElements();
        $newElements = [];
        $elementAdded = false;

        switch ($positionType) {
            case 'prepend':
                $newElements = array_merge([$newElement], $existingElements);
                $elementAdded = true;
                break;

            case 'append':
                $newElements = array_merge($existingElements, [$newElement]);
                $elementAdded = true;
                break;

            case 'before':
            case 'after':
                $targetUid = $position['elementUid'];
                foreach ($existingElements as $element) {
                    if ($element->uid === $targetUid && $positionType === 'before') {
                        $newElements[] = $newElement;
                        $elementAdded = true;
                    }
                    $newElements[] = $element;
                    if ($element->uid === $targetUid && $positionType === 'after') {
                        $newElements[] = $newElement;
                        $elementAdded = true;
                    }
                }
                throw_unless($elementAdded, "Element with UID '{$targetUid}' not found in tab '{$tabName}'");
                break;
        }

        $targetTab->setElements($newElements);

        $tabs = [];
        foreach ($fieldLayout->getTabs() as $tab) {
            $tabs[] = $tab->name === $tabName ? $targetTab : $tab;
        }
        $fieldLayout->setTabs($tabs);

        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        return [
            '_notes' => ['Field added successfully', 'Review the field layout in the control panel'],
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
            'addedField' => [
                'uid' => $newElement->uid,
                'fieldId' => $field->id,
                'fieldHandle' => $field->handle,
            ],
        ];
    }
}
