<?php

namespace happycog\craftmcp\actions;

use Craft;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Entries;
use craft\services\Fields;
use happycog\craftmcp\exceptions\ModelSaveException;

/**
 * Manages adding and removing EntryTitleField from field layouts
 * and keeps the entry type's hasTitleField property in sync.
 */
class ManageEntryTitleField
{
    public function __construct(
        protected Fields $fieldsService,
        protected Entries $entriesService,
    ) {
    }

    /**
     * Add EntryTitleField to a field layout and update associated entry type.
     *
     * @param FieldLayout $fieldLayout The field layout to modify
     * @param string|null $tabName Optional tab name to add to (uses first tab if not specified)
     * @return bool True if entry type was updated, false if already had title field
     */
    public function addTitleField(FieldLayout $fieldLayout, ?string $tabName = null): bool
    {
        // Check if title field already exists
        if ($fieldLayout->isFieldIncluded('title')) {
            return false;
        }

        $tabs = $fieldLayout->getTabs();
        
        // If no tabs exist, create a Content tab with the title field
        if (empty($tabs)) {
            $tabs = [new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => [new EntryTitleField()],
            ])];
        } else {
            // Find the target tab (first tab if not specified)
            $targetTabIndex = 0;
            if ($tabName !== null) {
                foreach ($tabs as $index => $tab) {
                    if ($tab->name === $tabName) {
                        $targetTabIndex = $index;
                        break;
                    }
                }
            }
            
            $targetTab = $tabs[$targetTabIndex];
            $elements = $targetTab->getElements();
            array_unshift($elements, new EntryTitleField());
            
            $tabs[$targetTabIndex] = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $targetTab->name,
                'elements' => $elements,
            ]);
        }
        
        $fieldLayout->setTabs($tabs);
        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        // Update associated entry type if found
        return $this->updateEntryTypeHasTitleField($fieldLayout, true);
    }

    /**
     * Remove EntryTitleField from a field layout and update associated entry type.
     *
     * @param FieldLayout $fieldLayout The field layout to modify
     * @return bool True if entry type was updated, false if didn't have title field
     */
    public function removeTitleField(FieldLayout $fieldLayout): bool
    {
        $titleFieldRemoved = false;
        $newTabs = [];
        
        foreach ($fieldLayout->getTabs() as $tab) {
            $newElements = [];
            foreach ($tab->getElements() as $element) {
                if ($element instanceof EntryTitleField) {
                    $titleFieldRemoved = true;
                    continue;
                }
                $newElements[] = $element;
            }
            $newTabs[] = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $tab->name,
                'elements' => $newElements,
            ]);
        }
        
        if (!$titleFieldRemoved) {
            return false;
        }
        
        $fieldLayout->setTabs($newTabs);
        throw_unless($this->fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        // Update associated entry type if found
        return $this->updateEntryTypeHasTitleField($fieldLayout, false);
    }

    /**
     * Update entry type's hasTitleField property if needed.
     *
     * Finds the entry type associated with the field layout and updates
     * its hasTitleField property to the specified value if it differs.
     *
     * @param FieldLayout $fieldLayout The field layout to find entry type for
     * @param bool $hasTitleField The desired hasTitleField value
     * @return bool True if entry type was updated, false otherwise
     */
    public function updateEntryTypeHasTitleField(FieldLayout $fieldLayout, bool $hasTitleField): bool
    {
        $entryType = $this->findEntryTypeByFieldLayout($fieldLayout);
        
        if ($entryType === null) {
            return false;
        }
        
        if ($entryType->hasTitleField === $hasTitleField) {
            return false;
        }
        
        $entryType->hasTitleField = $hasTitleField;
        throw_unless($this->entriesService->saveEntryType($entryType), ModelSaveException::class, $entryType);
        
        return true;
    }

    /**
     * Find an entry type that uses the given field layout.
     *
     * @param FieldLayout $fieldLayout The field layout to search for
     * @return EntryType|null The entry type or null if not found
     */
    public function findEntryTypeByFieldLayout(FieldLayout $fieldLayout): ?EntryType
    {
        $fieldLayoutId = $fieldLayout->id;
        if ($fieldLayoutId === null) {
            return null;
        }

        foreach ($this->entriesService->getAllEntryTypes() as $entryType) {
            if ($entryType->fieldLayoutId === $fieldLayoutId) {
                return $entryType;
            }
        }

        return null;
    }
}
