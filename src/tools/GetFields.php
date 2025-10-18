<?php

namespace happycog\craftmcp\tools;

use Craft;
use happycog\craftmcp\actions\FieldFormatter;

class GetFields
{
    public function __construct(
        protected FieldFormatter $fieldFormatter,
    ) {
    }

    /**
     * Get a list of all fields in Craft CMS. This is useful for understanding the available fields, their
     * configurations, and the field handle that must be used when creating or updating entries.
     *
     * You can pass an optional fieldLayoutId, if you know it, to only get the fields associated with that layout. Passing
     * null will return all global fields.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(
        /** Optional field layout ID to filter fields. Can be `null` to return all global fields. */
        ?int $fieldLayoutId = null
    ): array {
        return $fieldLayoutId
            ? $this->getFieldsForLayout($fieldLayoutId)
            : $this->getAllGlobalFields();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getFieldsForLayout(int $fieldLayoutId): array
    {
        $layout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);
        throw_unless($layout, "Field layout with ID {$fieldLayoutId} not found");

        // Preserve field ordering and include layout context
        return $this->fieldFormatter->formatFieldsForLayout($layout);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getAllGlobalFields(): array
    {
        $fields = Craft::$app->getFields()->getAllFields('global');
        $result = [];
        foreach ($fields as $field) {
            $result[] = $this->fieldFormatter->formatField($field);
        }

        return $result;
    }

}
