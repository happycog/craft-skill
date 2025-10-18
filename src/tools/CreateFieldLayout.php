<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\models\FieldLayout;
use happycog\craftmcp\exceptions\ModelSaveException;

class CreateFieldLayout
{
    /**
     * Create a new empty field layout in Craft CMS. This creates a basic field layout structure
     * that can be assigned to entry types, assets, users, or other elements that support field layouts.
     *
     * The created field layout will be empty with no tabs or fields initially. Use the update_field_layout
     * tool to add tabs and fields to the layout after creation.
     *
     * After creating the field layout always link the user back to the relevant settings in the Craft
     * control panel so they can review the changes in the context of the Craft UI.
     *
     * @return array<string, mixed>
     */
    public function create(
        /** The type of field layout to create (e.g., "craft\\elements\\Entry", "craft\\elements\\User", etc.) */
        string $type
    ): array {
        $fieldsService = Craft::$app->getFields();

        // Create a new field layout
        $fieldLayout = new FieldLayout([
            'type' => $type,
        ]);

        // Save the field layout
        throw_unless($fieldsService->saveLayout($fieldLayout), ModelSaveException::class, $fieldLayout);

        // Get the saved field layout ID
        $fieldLayoutId = $fieldLayout->id;
        throw_if($fieldLayoutId === null, \RuntimeException::class, "Field layout was saved but has no ID");

        // Retrieve the saved field layout to confirm
        $savedFieldLayout = $fieldsService->getLayoutById($fieldLayoutId);
        throw_unless($savedFieldLayout instanceof FieldLayout, \RuntimeException::class, "Failed to retrieve saved field layout with ID {$fieldLayoutId}");

        return [
            '_notes' => [
                'Field layout created successfully',
                'The field layout is empty and ready to have tabs and fields added via update_field_layout',
                'This field layout can now be assigned to entry types, users, assets, or other elements',
            ],
            'fieldLayoutId' => $savedFieldLayout->id,
            'type' => $savedFieldLayout->type,
            'tabs' => [], // Empty initially
        ];
    }
}