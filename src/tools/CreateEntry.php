<?php

namespace markhuot\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\models\Section;
use markhuot\craftmcp\actions\normalizers\SectionIdOrHandleToSectionId;
use markhuot\craftmcp\actions\UpsertEntry;

class CreateEntry
{
    /**
     * Create an entry in Craft.
     *
     * When creating a new entry pass an integer `$sectionId` and an integer `$entryTypeId`
     *
     * After creating the entry always link the user back to the entry in the Craft control panel so they can review
     * the changes in the context of the Craft UI.
     *
     * @param array $attributeAndFieldData The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. This field is idempotent so setting a field here will replace all field contents with the provided field contents.
     */
    public function __invoke(
        int|null $sectionId,
        int|null $entryTypeId,
        array $attributeAndFieldData = [],
    ): array
    {
        $upsertEntry = Craft::$container->get(UpsertEntry::class);

        $entry = $upsertEntry(
            sectionId: $sectionId,
            entryTypeId: $entryTypeId,
            attributeAndFieldData: $attributeAndFieldData,
        );

        return [
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($entry),
        ];
    }
}
