<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

class RemoveElementFromFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
    ) {
    }

    /**
     * Remove any element (field or UI element) from a field layout by its UID.
     *
     * Element UIDs can be obtained from get_field_layout. This works for any type
     * of field layout element including custom fields, native fields, and UI elements.
     *
     * @return array<string, mixed>
     */
    public function remove(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /** The UID of the element to remove */
        string $elementUid,
    ): array {
        $fieldLayout = $this->fieldsService->getLayoutById($fieldLayoutId);
        throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");

        $elementFound = false;
        $newTabs = [];

        foreach ($fieldLayout->getTabs() as $tab) {
            $newElements = [];
            foreach ($tab->getElements() as $element) {
                if ($element->uid === $elementUid) {
                    $elementFound = true;
                    continue;
                }
                $newElements[] = $element;
            }

            $newTab = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $tab->name,
                'elements' => $newElements,
            ]);
            $newTabs[] = $newTab;
        }

        throw_unless($elementFound, "Element with UID '{$elementUid}' not found in field layout");

        $fieldLayout->setTabs($newTabs);
        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        return [
            '_notes' => ['Element removed successfully', 'Review the field layout in the control panel'],
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
        ];
    }
}
