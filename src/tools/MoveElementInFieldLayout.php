<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\base\FieldLayoutElement;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

class MoveElementInFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
    ) {
    }

    /**
     * Move an existing element to a new position within the same tab or to a different tab.
     *
     * This allows reordering elements within a tab or relocating them to different tabs.
     * Element UIDs can be obtained from get_field_layout.
     *
     * @param array<string, mixed> $position
     * @return array<string, mixed>
     */
    public function move(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /** The UID of the element to move */
        string $elementUid,

        /** Name of the target tab to move the element to */
        string $tabName,

        /**
         * Positioning configuration:
         * - type: 'before', 'after', 'prepend', or 'append' (required)
         * - elementUid: UID of existing element for 'before' or 'after' positioning
         */
        array $position,
    ): array {
        $fieldLayout = $this->fieldsService->getLayoutById($fieldLayoutId);
        throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");

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
        throw_unless($targetTab !== null, "Tab with name '{$tabName}' not found");

        $elementToMove = null;
        $newTabs = [];

        foreach ($fieldLayout->getTabs() as $tab) {
            $newElements = [];
            foreach ($tab->getElements() as $element) {
                if ($element->uid === $elementUid) {
                    $elementToMove = $element;
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

        throw_unless($elementToMove !== null, "Element with UID '{$elementUid}' not found in field layout");

        $finalTabs = [];
        foreach ($newTabs as $tab) {
            if ($tab->name !== $tabName) {
                $finalTabs[] = $tab;
                continue;
            }

            $existingElements = $tab->getElements();
            $newElements = [];
            $elementAdded = false;

            switch ($positionType) {
                case 'prepend':
                    $newElements = array_merge([$elementToMove], $existingElements);
                    $elementAdded = true;
                    break;

                case 'append':
                    $newElements = array_merge($existingElements, [$elementToMove]);
                    $elementAdded = true;
                    break;

                case 'before':
                case 'after':
                    $targetUid = $position['elementUid'];
                    foreach ($existingElements as $element) {
                        if ($element->uid === $targetUid && $positionType === 'before') {
                            $newElements[] = $elementToMove;
                            $elementAdded = true;
                        }
                        $newElements[] = $element;
                        if ($element->uid === $targetUid && $positionType === 'after') {
                            $newElements[] = $elementToMove;
                            $elementAdded = true;
                        }
                    }
                    throw_unless($elementAdded, "Element with UID '{$position['elementUid']}' not found in tab '{$tabName}'");
                    break;
            }

            $finalTab = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $tab->name,
                'elements' => $newElements,
            ]);
            $finalTabs[] = $finalTab;
        }

        $fieldLayout->setTabs($finalTabs);
        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        return [
            '_notes' => ['Element moved successfully', 'Review the field layout in the control panel'],
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
        ];
    }
}
