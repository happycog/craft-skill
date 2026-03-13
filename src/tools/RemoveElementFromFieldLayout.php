<?php

namespace happycog\craftmcp\tools;

use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Fields;
use happycog\craftmcp\actions\ManageEntryTitleField;
use happycog\craftmcp\actions\NormalizeAddressFieldLayoutForSave;
use happycog\craftmcp\actions\ResolveFieldLayout;
use happycog\craftmcp\actions\ResolvePersistedAddressFieldLayout;
use happycog\craftmcp\actions\SaveFieldLayout;
use happycog\craftmcp\exceptions\ModelSaveException;
use happycog\craftmcp\tools\GetAddressFieldLayout;

class RemoveElementFromFieldLayout
{
    public function __construct(
        protected Fields $fieldsService,
        protected GetFieldLayout $getFieldLayout,
        protected ManageEntryTitleField $manageEntryTitleField,
        protected NormalizeAddressFieldLayoutForSave $normalizeAddressFieldLayoutForSave,
        protected ResolveFieldLayout $resolveFieldLayout,
        protected ResolvePersistedAddressFieldLayout $resolvePersistedAddressFieldLayout,
        protected SaveFieldLayout $saveFieldLayout,
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
    public function __invoke(
        /** The ID of the field layout to modify */
        int $fieldLayoutId,

        /** The UID of the element to remove */
        string $elementUid,
    ): array {
        $fieldLayout = ($this->resolveFieldLayout)($fieldLayoutId);
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

        $fieldLayoutToSave = $fieldLayoutId === GetAddressFieldLayout::PLACEHOLDER_ID
            ? ($this->normalizeAddressFieldLayoutForSave)($fieldLayout)
            : $fieldLayout;

        throw_unless(($this->saveFieldLayout)($fieldLayoutToSave), ModelSaveException::class, $fieldLayoutToSave);

        if ($fieldLayoutId === GetAddressFieldLayout::PLACEHOLDER_ID) {
            $fieldLayout = ($this->resolvePersistedAddressFieldLayout)();
        } else {
            $fieldLayout = ($this->resolveFieldLayout)($fieldLayoutId) ?? $fieldLayout;
        }

        $notes = ['Element removed successfully'];
        
        // If we removed an EntryTitleField, update the associated entry type
        if ($removedElement instanceof EntryTitleField) {
            if ($this->manageEntryTitleField->updateEntryTypeHasTitleField($fieldLayout, false)) {
                $notes[] = 'Entry type updated: hasTitleField set to false';
                $notes[] = 'IMPORTANT: You must now set a titleFormat on this entry type using update_entry_type to define how titles are automatically generated. Example: titleFormat: "{dateCreated|date}" or titleFormat: "{fieldHandle}"';
            }
        }
        
        $notes[] = 'Review the field layout in the control panel';

        return [
            '_notes' => $notes,
            'fieldLayout' => $this->getFieldLayout->formatFieldLayout($fieldLayout),
        ];
    }


}
