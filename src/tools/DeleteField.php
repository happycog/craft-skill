<?php

namespace happycog\craftmcp\tools;

use Craft;
use happycog\craftmcp\exceptions\ModelSaveException;

class DeleteField
{
    /**
     * Delete a field from Craft CMS. Field deletion is permanent and cannot be undone.
     *
     * **WARNING**: Deleting a field will remove all content stored in that field across all entries.
     * This action is permanent and cannot be undone.
     *
     * The tool will show which layouts and sections are using the field before deletion
     * to help you understand the impact. The tool will also clean up any field layout elements
     * that reference the deleted field to prevent broken references.
     *
     * @return array<string, mixed>
     */
    public function delete(
        /** The ID of the field to delete */
        int $fieldId
    ): array
    {
        $fieldsService = Craft::$app->getFields();
        
        // Get the field to delete
        $field = $fieldsService->getFieldById($fieldId);
        if (!$field) {
            throw new \InvalidArgumentException("Field with ID {$fieldId} does not exist.");
        }
        
        // Find field usages before deletion
        $usages = $fieldsService->findFieldUsages($field);
        $usageInfo = [];
        $affectedLayoutIds = [];
        
        foreach ($usages as $usage) {
            $usageInfo[] = [
                'context' => $usage['context'] ?? 'unknown',
                'layout' => $usage['layout'] ?? null,
                'type' => isset($usage['layout']) && is_object($usage['layout']) ? get_class($usage['layout']) : 'unknown',
            ];
            
            // Track field layout IDs for cleanup
            if (isset($usage['layout']) && is_object($usage['layout']) && method_exists($usage['layout'], 'getId')) {
                $layoutId = $usage['layout']->getId();
                if ($layoutId !== null) {
                    $affectedLayoutIds[] = $layoutId;
                }
            }
        }
        
        // Store field info before deletion
        $fieldInfo = [
            'id' => $field->id,
            'name' => $field->name,
            'handle' => $field->handle,
            'type' => get_class($field),
            'usages' => $usageInfo,
            'usageCount' => count($usages),
        ];
        
        // Perform the deletion
        try {
            throw_unless($fieldsService->deleteField($field), ModelSaveException::class, $field);
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete field: " . $e->getMessage());
        }
        
        // Clean up orphaned field layout elements after deletion
        $cleanedLayoutCount = 0;
        foreach (array_unique($affectedLayoutIds) as $layoutId) {
            $layout = $fieldsService->getLayoutById($layoutId);
            if ($layout instanceof \craft\models\FieldLayout) {
                $modified = false;
                $newTabs = [];
                
                foreach ($layout->getTabs() as $tab) {
                    $newElements = [];
                    foreach ($tab->getElements() as $element) {
                        // Keep element only if it's not a CustomField referencing the deleted field
                        if ($element instanceof \craft\fieldlayoutelements\CustomField) {
                            $elementField = $element->getField();
                            // @phpstan-ignore identical.alwaysFalse (getField() returns null after field is deleted)
                            if ($elementField === null || $elementField->id === $fieldId) {
                                $modified = true;
                                continue; // Skip this element
                            }
                        }
                        $newElements[] = $element;
                    }
                    
                    $newTab = new \craft\models\FieldLayoutTab([
                        'layout' => $layout,
                        'name' => $tab->name,
                        'uid' => $tab->uid,
                        'elements' => $newElements,
                    ]);
                    $newTabs[] = $newTab;
                }
                
                if ($modified) {
                    $layout->setTabs($newTabs);
                    $fieldsService->saveLayout($layout);
                    $cleanedLayoutCount++;
                }
            }
        }
        
        // Generate warning message
        $warningMessage = "Field '{$fieldInfo['name']}' has been permanently deleted and cannot be restored.";
            
        if ($fieldInfo['usageCount'] > 0) {
            $warningMessage .= " This field was used in {$fieldInfo['usageCount']} layout(s) and all associated content has been removed.";
        }
        
        if ($cleanedLayoutCount > 0) {
            $warningMessage .= " Cleaned up {$cleanedLayoutCount} field layout(s) to remove broken references.";
        }
        
        return [
            '_notes' => $warningMessage,
            'deletedField' => $fieldInfo,
            'affectedLayouts' => $fieldInfo['usageCount'],
            'cleanedLayouts' => $cleanedLayoutCount,
            'success' => true,
        ];
    }
}
