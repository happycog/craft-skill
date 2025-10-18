<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\UrlHelper;
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
     * to help you understand the impact.
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
        
        foreach ($usages as $usage) {
            $usageInfo[] = [
                'context' => $usage['context'] ?? 'unknown',
                'layout' => $usage['layout'] ?? null,
                'type' => isset($usage['layout']) && is_object($usage['layout']) ? get_class($usage['layout']) : 'unknown',
            ];
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
        
        // Generate warning message
        $warningMessage = "Field '{$fieldInfo['name']}' has been permanently deleted and cannot be restored.";
            
        if ($fieldInfo['usageCount'] > 0) {
            $warningMessage .= " This field was used in {$fieldInfo['usageCount']} layout(s) and all associated content has been removed.";
        }
        
        return [
            '_notes' => $warningMessage,
            'deletedField' => $fieldInfo,
            'affectedLayouts' => $fieldInfo['usageCount'],
            'success' => true,
        ];
    }
}