<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\base\FieldInterface;
use craft\helpers\UrlHelper;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class UpdateField
{
    /**
     * @param array<string, mixed>|null $settings
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'update_field',
        description: <<<'END'
        Update an existing field in Craft CMS. This tool allows you to modify field properties
        including name, instructions, settings, and some aspects of field configuration.
        
        Note: Changing field types is technically possible but may result in data loss. Use with caution
        and test in development environments first.
        
        After updating the field always link the user back to the field settings in the Craft control 
        panel so they can review the changes in the context of the Craft UI.
        END
    )]
    public function update(
        #[Schema(type: 'number', description: 'The ID of the field to update')]
        int $fieldId,
        
        #[Schema(type: 'string', description: 'The new display name for the field')]
        ?string $name = null,
        
        #[Schema(type: 'string', description: 'The new field handle (machine-readable name)')]
        ?string $handle = null,
        
        #[Schema(type: 'string', description: 'New instructions to help content editors use this field')]
        ?string $instructions = null,
        
        #[Schema(type: 'boolean', description: 'Whether the field values should be searchable')]
        ?bool $searchable = null,
        
        #[Schema(type: 'string', description: 'Translation method: none, site, language, or custom')]
        ?string $translationMethod = null,
        
        #[Schema(type: 'object', description: 'Field type-specific settings as key-value pairs')]
        ?array $settings = null,
        
        #[Schema(type: 'string', description: 'New field type class name (use with caution - may cause data loss)')]
        ?string $type = null
    ): array
    {
        $fieldsService = Craft::$app->getFields();
        
        // Get the existing field
        $existingField = $fieldsService->getFieldById($fieldId);
        if (!$existingField) {
            throw new \InvalidArgumentException("Field with ID {$fieldId} does not exist.");
        }
        
        // Clone for comparison
        $oldField = clone $existingField;
        
        // Validate new handle if provided
        if ($handle && $handle !== $existingField->handle) {
            $conflictingField = $fieldsService->getFieldByHandle($handle);
            if ($conflictingField && $conflictingField->id !== $fieldId) {
                throw new \InvalidArgumentException("A field with handle '{$handle}' already exists.");
            }
        }
        
        // Validate new field type if provided
        if ($type && $type !== get_class($existingField)) {
            $availableTypes = $fieldsService->getAllFieldTypes();
            if (!in_array($type, $availableTypes)) {
                throw new \InvalidArgumentException("Field type '{$type}' is not available.");
            }
            
            if (!$type::isSelectable()) {
                throw new \InvalidArgumentException("Field type '{$type}' is not selectable by users.");
            }
        }
        
        // Prepare field configuration
        $fieldType = $type ?: get_class($existingField);
        throw_unless(is_a($fieldType, FieldInterface::class, true), "Invalid field type: {$fieldType}");
        throw_unless($existingField->uid, 'Field UID is required');
        
        /** @var array{type: class-string<FieldInterface>, id: int, uid: string} $fieldConfig */
        $fieldConfig = [
            'type' => $fieldType,
            'id' => $fieldId,
            'uid' => $existingField->uid,
            'columnSuffix' => $existingField->columnSuffix,
            'name' => $name !== null ? $name : $existingField->name,
            'handle' => $handle !== null ? $handle : $existingField->handle,
            'instructions' => $instructions !== null ? $instructions : $existingField->instructions,
            'searchable' => $searchable !== null ? $searchable : $existingField->searchable,
            'translationMethod' => $translationMethod !== null ? 
                $this->getTranslationMethodConstant($translationMethod) : 
                $existingField->translationMethod,
            'settings' => $settings !== null ? 
                array_merge($existingField->settings, $settings) : 
                $existingField->settings,
        ];
        
        // Create the updated field
        $field = $fieldsService->createField($fieldConfig);
        
        // Save the field
        throw_unless($fieldsService->saveField($field), ModelSaveException::class, $field);
        
        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/fields/edit/' . $field->id);
        
        // Identify what changed
        $changes = [];
        if ($name !== null && $name !== $oldField->name) {
            $changes[] = "name: '{$oldField->name}' → '{$name}'";
        }
        if ($handle !== null && $handle !== $oldField->handle) {
            $changes[] = "handle: '{$oldField->handle}' → '{$handle}'";
        }
        if ($instructions !== null && $instructions !== $oldField->instructions) {
            $changes[] = "instructions updated";
        }
        if ($searchable !== null && $searchable !== $oldField->searchable) {
            $changes[] = "searchable: " . ($oldField->searchable ? 'true' : 'false') . " → " . ($searchable ? 'true' : 'false');
        }
        if ($type !== null && $type !== get_class($oldField)) {
            $changes[] = "type: " . get_class($oldField) . " → {$type}";
        }
        if ($settings !== null) {
            $changes[] = "settings updated";
        }
        if ($translationMethod !== null && $this->getTranslationMethodConstant($translationMethod) !== $oldField->translationMethod) {
            $changes[] = "translation method updated";
        }
        
        return [
            '_notes' => 'The field was successfully updated. Changes: ' . (empty($changes) ? 'none' : implode(', ', $changes)),
            'fieldId' => $field->id,
            'name' => $field->name,
            'handle' => $field->handle,
            'type' => get_class($field),
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'changes' => $changes,
            'editUrl' => $editUrl,
        ];
    }
    
    private function getTranslationMethodConstant(string $method): string
    {
        $methodMap = [
            'none' => \craft\base\Field::TRANSLATION_METHOD_NONE,
            'site' => \craft\base\Field::TRANSLATION_METHOD_SITE,
            'language' => \craft\base\Field::TRANSLATION_METHOD_LANGUAGE,
            'custom' => \craft\base\Field::TRANSLATION_METHOD_CUSTOM,
        ];
        
        if (!isset($methodMap[$method])) {
            throw new \InvalidArgumentException("Invalid translation method '{$method}'. Must be one of: " . implode(', ', array_keys($methodMap)));
        }
        
        return $methodMap[$method];
    }
}