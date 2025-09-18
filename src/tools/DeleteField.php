<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\UrlHelper;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class DeleteField
{
    #[McpTool(
        name: 'delete_field',
        description: <<<'END'
        Delete a field from Craft CMS. This tool supports both soft deletion (default) and permanent
        deletion of fields.
        
        **WARNING**: Deleting a field will remove all content stored in that field across all entries.
        This action cannot be undone if permanent deletion is used.
        
        By default, this performs a soft delete where the field is removed but can potentially be
        restored. Set permanentlyDelete to true for permanent removal.
        
        The tool will also show which layouts and sections are using the field before deletion
        to help you understand the impact.
        END
    )]
    public function delete(
        #[Schema(type: 'number', description: 'The ID of the field to delete')]
        int $fieldId,
        
        #[Schema(type: 'boolean', description: 'Set to true to permanently delete the field. Default is false (soft delete).')]
        bool $permanentlyDelete = false
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
                'type' => get_class($usage['layout'] ?? null) ?: 'unknown',
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
            $success = $fieldsService->deleteField($field);
            
            if (!$success) {
                // Get any errors from the field model
                $errors = $field->getErrors();
                $errorMessages = [];
                foreach ($errors as $attribute => $attributeErrors) {
                    foreach ($attributeErrors as $error) {
                        $errorMessages[] = "{$attribute}: {$error}";
                    }
                }
                
                if (empty($errorMessages)) {
                    throw new \Exception("Failed to delete field for unknown reason.");
                } else {
                    throw new \Exception("Failed to delete field: " . implode(', ', $errorMessages));
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete field: " . $e->getMessage());
        }
        
        // Generate appropriate warning message
        $warningMessage = $permanentlyDelete 
            ? "Field '{$fieldInfo['name']}' has been permanently deleted and cannot be restored."
            : "Field '{$fieldInfo['name']}' has been deleted.";
            
        if ($fieldInfo['usageCount'] > 0) {
            $warningMessage .= " This field was used in {$fieldInfo['usageCount']} layout(s) and all associated content has been removed.";
        }
        
        return [
            '_notes' => $warningMessage,
            'deletedField' => $fieldInfo,
            'permanentlyDeleted' => $permanentlyDelete,
            'affectedLayouts' => $fieldInfo['usageCount'],
            'success' => true,
        ];
    }
}