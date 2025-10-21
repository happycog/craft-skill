<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

class AddTabToFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
    ) {
    }

    /**
     * Add a new tab to a field layout at a specific position.
     *
     * Tabs must be explicitly created before adding fields or UI elements to them.
     * Position can be before/after a specific tab, or prepend/append to the tab list.
     *
     * @param array<string, mixed> $position
     * @return array<string, mixed>
     */
    public function add(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /** The name of the new tab */
        string $name,

        /**
         * Positioning configuration:
         * - type: 'before', 'after', 'prepend', or 'append' (required)
         * - tabName: Name of existing tab for 'before' or 'after' positioning
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
                isset($position['tabName']) && is_string($position['tabName']),
                "tabName is required for 'before' and 'after' positioning"
            );
        }

        $existingTabs = $fieldLayout->getTabs();
        $newTab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => $name,
            'elements' => [],
        ]);

        $newTabs = [];
        $tabAdded = false;

        switch ($positionType) {
            case 'prepend':
                $newTabs = array_merge([$newTab], $existingTabs);
                $tabAdded = true;
                break;

            case 'append':
                $newTabs = array_merge($existingTabs, [$newTab]);
                $tabAdded = true;
                break;

            case 'before':
            case 'after':
                $targetTabName = $position['tabName'];
                foreach ($existingTabs as $tab) {
                    if ($tab->name === $targetTabName && $positionType === 'before') {
                        $newTabs[] = $newTab;
                        $tabAdded = true;
                    }
                    $newTabs[] = $tab;
                    if ($tab->name === $targetTabName && $positionType === 'after') {
                        $newTabs[] = $newTab;
                        $tabAdded = true;
                    }
                }
                throw_unless($tabAdded, "Tab with name '{$targetTabName}' not found");
                break;
        }

        $fieldLayout->setTabs($newTabs);
        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        return [
            '_notes' => ['Tab added successfully', 'Review the field layout in the control panel'],
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
        ];
    }
}
