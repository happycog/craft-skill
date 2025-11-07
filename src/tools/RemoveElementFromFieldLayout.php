<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\fieldlayoutelements\entries\EntryTitleField;
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
     * If removing an EntryTitleField, this will also update the associated entry type's
     * hasTitleField property to false to keep the database and UI consistent.
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
        $removedElement = null;
        $newTabs = [];

        foreach ($fieldLayout->getTabs() as $tab) {
            $newElements = [];
            foreach ($tab->getElements() as $element) {
                if ($element->uid === $elementUid) {
                    $elementFound = true;
                    $removedElement = $element;
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

        // If we removed an EntryTitleField, update the associated entry type
        $entryTypeUpdated = false;
        if ($removedElement instanceof EntryTitleField) {
            $entryType = $this->findEntryTypeByFieldLayoutId($fieldLayoutId);
            if ($entryType !== null) {
                $entryType->hasTitleField = false;
                $entriesService = Craft::$app->getEntries();
                throw_unless($entriesService->saveEntryType($entryType), ModelSaveException::class, $entryType);
                $entryTypeUpdated = true;
            }
        }

        $notes = ['Element removed successfully'];
        if ($entryTypeUpdated) {
            $notes[] = 'Entry type updated: hasTitleField set to false';
            $notes[] = 'IMPORTANT: You must now set a titleFormat on this entry type using update_entry_type to define how titles are automatically generated. Example: titleFormat: "{dateCreated|date}" or titleFormat: "{fieldHandle}"';
        }
        $notes[] = 'Review the field layout in the control panel';

        return [
            '_notes' => $notes,
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
        ];
    }

    /**
     * Find an entry type that uses the given field layout ID.
     *
     * @return \craft\models\EntryType|null
     */
    private function findEntryTypeByFieldLayoutId(int $fieldLayoutId): ?\craft\models\EntryType
    {
        $entriesService = Craft::$app->getEntries();

        foreach ($entriesService->getAllEntryTypes() as $entryType) {
            if ($entryType->fieldLayoutId === $fieldLayoutId) {
                return $entryType;
            }
        }

        return null;
    }
}
