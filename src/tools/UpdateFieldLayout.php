<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class UpdateFieldLayout
{
    /**
     * @param array<int, array<string, mixed>> $tabs
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'update_field_layout',
        description: <<<'END'
        Update a field layout by organizing fields into tabs and setting field properties like required status
        and controlling field order. Works with any Craft model that has a field layout (entry types, users,
        assets, etc.).

        After updating the field layout always link the user back to the relevant settings in the Craft control
        panel so they can review the changes in the context of the Craft UI.
        END
    )]
    public function update(
        #[Schema(type: 'integer', description: 'The ID of the field layout to update')]
        int $fieldLayoutId,

        #[Schema(
            type: 'array',
            description: 'Array of tabs to organize fields. Tabs will be created in the order provided.',
            items: [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'The display name for the tab'
                    ],
                    'fields' => [
                        'type' => 'array',
                        'description' => 'Array of field configurations for this tab',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'fieldId' => [
                                    'type' => 'integer',
                                    'description' => 'The ID of the field to add to this tab'
                                ],
                                'required' => [
                                    'type' => 'boolean',
                                    'description' => 'Whether this field is required (default: false)',
                                    'default' => false
                                ],
                                'width' => [
                                    'type' => 'integer',
                                    'description' => 'Width percentage for the field (25, 50, 75, or 100)',
                                    'enum' => [25, 50, 75, 100],
                                    'default' => 100
                                ]
                            ],
                            'required' => ['fieldId']
                        ]
                    ]
                ],
                'required' => ['name', 'fields']
            ]
        )]
        array $tabs
    ): array {
        $fieldsService = Craft::$app->getFields();

        // Get the field layout directly
        $fieldLayout = $fieldsService->getLayoutById($fieldLayoutId);
        \throw_unless($fieldLayout instanceof FieldLayout, "Field layout with ID {$fieldLayoutId} not found");        // Validate all field IDs exist before proceeding
        $allFieldIds = [];
        foreach ($tabs as $tabData) {
            assert(is_array($tabData));
            assert(isset($tabData['fields']) && is_array($tabData['fields']));

            foreach ($tabData['fields'] as $fieldConfig) {
                assert(is_array($fieldConfig));
                assert(isset($fieldConfig['fieldId']) && is_int($fieldConfig['fieldId']));
                $allFieldIds[] = $fieldConfig['fieldId'];
            }
        }

        // Validate all fields exist
        foreach ($allFieldIds as $fieldId) {
            $field = $fieldsService->getFieldById($fieldId);
            \throw_unless($field !== null, "Field with ID {$fieldId} not found");
        }

        // Create new tabs
        $newTabs = [];
        foreach ($tabs as $tabData) {
            assert(is_array($tabData));
            assert(isset($tabData['name']) && is_string($tabData['name']));
            assert(isset($tabData['fields']) && is_array($tabData['fields']));

            $elements = [];
            foreach ($tabData['fields'] as $fieldConfig) {
                assert(is_array($fieldConfig));
                assert(isset($fieldConfig['fieldId']) && is_int($fieldConfig['fieldId']));

                $fieldId = $fieldConfig['fieldId'];
                $required = $fieldConfig['required'] ?? false;
                $width = $fieldConfig['width'] ?? 100;

                assert(is_bool($required));
                assert(is_int($width) && in_array($width, [25, 50, 75, 100], true));

                $field = $fieldsService->getFieldById($fieldId);
                \throw_unless($field !== null, "Field with ID {$fieldId} not found");

                $customFieldElement = new CustomField($field);
                $customFieldElement->required = $required;
                $customFieldElement->width = $width;

                $elements[] = $customFieldElement;
            }

            $tab = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $tabData['name'],
                'elements' => $elements,
            ]);

            $newTabs[] = $tab;
        }

        // Update the field layout with new tabs
        $fieldLayout->setTabs($newTabs);

        // Save the field layout
        throw_unless($fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        // Get updated field layout information
        $updatedFieldLayout = $fieldsService->getLayoutById($fieldLayoutId);
        \throw_unless($updatedFieldLayout instanceof FieldLayout, "Updated field layout not found");

        $fieldLayoutInfo = [
            'id' => $updatedFieldLayout->id,
            'type' => $updatedFieldLayout->type,
            'tabs' => [],
        ];

        foreach ($updatedFieldLayout->getTabs() as $tab) {
            $tabInfo = [
                'name' => $tab->name,
                'fields' => [],
            ];

            foreach ($tab->getElements() as $element) {
                if ($element instanceof CustomField) {
                    $field = $element->getField();
                    if ($field !== null) {
                        $tabInfo['fields'][] = [
                            'id' => $field->id,
                            'name' => $field->name,
                            'handle' => $field->handle,
                            'type' => get_class($field),
                            'required' => $element->required,
                            'width' => $element->width,
                        ];
                    }
                }
            }

            $fieldLayoutInfo['tabs'][] = $tabInfo;
        }

        return [
            '_notes' => [
                'Field layout updated successfully',
                'Fields have been organized into the specified tabs with the configured properties',
                'The field layout can now be used by any model that references it',
            ],
            'fieldLayout' => $fieldLayoutInfo,
        ];
    }
}
