<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\UrlHelper;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class CreateField
{
    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'create_field',
        description: <<<'END'
        Create a new field in Craft CMS. This tool allows you to create fields of any type available
        in the installation, including those added by plugins.
        
        Use the GetFieldTypes tool first to discover available field types and their class names.
        
        The field will be created with the specified configuration and you'll receive a control panel
        URL to review and further configure the field settings.
        
        After creating the field always link the user back to the field settings in the Craft control 
        panel so they can review and further configure the field in the context of the Craft UI.
        END
    )]
    public function create(
        #[Schema(type: 'string', description: 'The field type class name (use GetFieldTypes to discover available types)')]
        string $type,
        
        #[Schema(type: 'string', description: 'The display name for the field')]
        string $name,
        
        #[Schema(type: 'string', description: 'The field handle (machine-readable name). Auto-generated from name if not provided.')]
        ?string $handle = null,
        
        #[Schema(type: 'string', description: 'Instructions to help content editors use this field')]
        ?string $instructions = null,
        
        #[Schema(type: 'boolean', description: 'Whether the field values should be searchable')]
        bool $searchable = true,
        
        #[Schema(type: 'string', description: 'Translation method: none, site, language, or custom')]
        string $translationMethod = 'none',
        
        #[Schema(type: 'object', description: 'Field type-specific settings as key-value pairs')]
        array $settings = []
    ): array
    {
        $fieldsService = Craft::$app->getFields();
        
        // Validate field type exists and is selectable
        $availableTypes = $fieldsService->getAllFieldTypes();
        if (!in_array($type, $availableTypes)) {
            throw new \InvalidArgumentException("Field type '{$type}' is not available.");
        }
        
        if (!$type::isSelectable()) {
            throw new \InvalidArgumentException("Field type '{$type}' is not selectable by users.");
        }
        
        // Generate handle if not provided
        if (!$handle) {
            $handle = $this->generateHandle($name);
        }
        
        // Validate handle is unique
        $existingField = $fieldsService->getFieldByHandle($handle);
        if ($existingField) {
            throw new \InvalidArgumentException("A field with handle '{$handle}' already exists.");
        }
        
        // Map translation method
        $translationMethodConstant = $this->getTranslationMethodConstant($translationMethod);
        
        // Create field configuration
        $fieldConfig = [
            'type' => $type,
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions ?: '',
            'searchable' => $searchable,
            'translationMethod' => $translationMethodConstant,
            'settings' => $settings,
        ];
        
        // Create the field
        $field = $fieldsService->createField($fieldConfig);
        
        // Save the field
        throw_unless($fieldsService->saveField($field), ModelSaveException::class, $field);
        
        // Generate control panel URL
        $editUrl = UrlHelper::cpUrl('settings/fields/edit/' . $field->id);
        
        return [
            '_notes' => 'The field was successfully created. You can further configure it in the Craft control panel.',
            'fieldId' => $field->id,
            'name' => $field->name,
            'handle' => $field->handle,
            'type' => get_class($field),
            'instructions' => $field->instructions,
            'searchable' => $field->searchable,
            'translationMethod' => $field->translationMethod,
            'editUrl' => $editUrl,
        ];
    }
    
    private function generateHandle(string $name): string
    {
        // Convert to camelCase handle
        $handle = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
        $handle = ucwords(strtolower($handle ?? ''));
        $handle = str_replace(' ', '', $handle);
        $handle = lcfirst($handle);
        
        // Ensure it doesn't start with a number
        if (preg_match('/^[0-9]/', $handle)) {
            $handle = 'field' . ucfirst($handle);
        }
        
        return $handle;
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