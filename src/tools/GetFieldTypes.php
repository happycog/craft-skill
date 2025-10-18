<?php

namespace happycog\craftmcp\tools;

use Craft;

class GetFieldTypes
{
    /**
     * Get a list of all available field types in the Craft CMS installation. This returns field types
     * that can be created by users, including those added by plugins. Each field type includes its
     * class name, display name, and description to help with field creation.
     *
     * Use this tool to discover what field types are available before creating fields with the
     * CreateField tool.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $fieldTypes = Craft::$app->getFields()->getAllFieldTypes();
        $result = [];

        foreach ($fieldTypes as $fieldTypeClass) {
            // Only include field types that can be selected by users
            if (!$fieldTypeClass::isSelectable()) {
                continue;
            }

            $result[] = [
                'class' => $fieldTypeClass,
                'name' => $fieldTypeClass::displayName(),
                'icon' => $fieldTypeClass::icon(),
                'description' => $this->getFieldTypeDescription($fieldTypeClass),
            ];
        }

        // Sort by display name for easier browsing
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    private function getFieldTypeDescription(string $fieldTypeClass): string
    {
        // Provide common use case descriptions for built-in field types
        $descriptions = [
            'craft\fields\PlainText' => 'Single-line or multi-line text input for basic content',
            'craft\fields\Textarea' => 'Multi-line text input for longer content',
            'craft\fields\RichText' => 'Rich text editor with formatting options',
            'craft\fields\Number' => 'Numeric input with optional validation and formatting',
            'craft\fields\Dropdown' => 'Dropdown selection from predefined options',
            'craft\fields\Checkboxes' => 'Multiple checkbox selections from predefined options',
            'craft\fields\RadioButtons' => 'Single selection from predefined radio button options',
            'craft\fields\Lightswitch' => 'On/off toggle switch for boolean values',
            'craft\fields\Email' => 'Email address input with validation',
            'craft\fields\Url' => 'URL input with validation',
            'craft\fields\Date' => 'Date and/or time picker',
            'craft\fields\Assets' => 'File and image upload/selection',
            'craft\fields\Entries' => 'Relationship to other entries',
            'craft\fields\Categories' => 'Relationship to categories',
            'craft\fields\Users' => 'Relationship to users',
            'craft\fields\Tags' => 'Tag assignment and creation',
            'craft\fields\Matrix' => 'Flexible content blocks with nested fields',
            'craft\fields\Table' => 'Tabular data with rows and columns',
            'craft\fields\Color' => 'Color picker for hex color values',
        ];

        return $descriptions[$fieldTypeClass] ?? 'Custom field type provided by plugin';
    }
}