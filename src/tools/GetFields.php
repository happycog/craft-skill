<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\fields\Matrix;
use PhpMcp\Server\Attributes\McpTool;

class GetFields
{
    #[McpTool(
        name: 'get_fields',
        description: <<<'END'
        Get a list of all fields in Craft CMS. This is useful for understanding the available fields, their
        configurations, and the field handle that must be used when creating or updating entries.
        
        You can pass an optional fieldLayoutId, if you know it, to only get the fields associated with that layout.
        END
    )]
    public function get(?int $fieldLayoutId): array
    {
        if ($fieldLayoutId) {
            $layout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);
            $fields = $layout->getCustomFields();
        }
        else {
            $fields = Craft::$app->getFields()->getAllFields('global');
        }

        $result = [];
        foreach ($fields as $field) {
            $result[] = $this->formatField($field);
        }

        return $result;
    }

    private function formatField($field): array
    {
        $fieldData = [
            'id' => $field->id,
            'handle' => $field->handle,
            'name' => $field->name,
            'type' => get_class($field),
            'instructions' => $field->instructions,
            'required' => $field->required,
        ];

        // Handle nested fields for Matrix fields
        if ($field instanceof Matrix) {
            $blockTypes = [];
            foreach ($field->getEntryTypes() as $entryType) {
                $blockFields = [];
                foreach ($entryType->getCustomFields() as $blockField) {
                    $blockFields[] = $this->formatField($blockField);
                }

                $blockTypes[] = [
                    'id' => $entryType->id,
                    'handle' => $entryType->handle,
                    'name' => $entryType->name,
                    'fields' => $blockFields,
                ];
            }
            $fieldData['blockTypes'] = $blockTypes;
        }

        return $fieldData;
    }
}
