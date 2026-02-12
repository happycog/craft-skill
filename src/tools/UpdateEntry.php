<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\models\Section;
use happycog\craftmcp\actions\normalizers\SectionIdOrHandleToSectionId;
use happycog\craftmcp\actions\UpsertEntry;

class UpdateEntry
{
    public function __construct(
        protected UpsertEntry $upsertEntry,
    ) {
    }

    /**
     * Update an entry in Craft.
     *
     * Entry Information:
     * - An "Entry" in Craft is a generic term. Entries could hold categories, media, and a variety of other data types.
     * - Query sections first to get the types of entries that can be updated. Use the section type and definition.
     * - Prefer creating a Draft instead of updating an entry in place. Use CreateDraft and UpdateDraft tools so the
     * user can review changes in the Craft UI before accepting them.
     *
     * After updating the entry always link the user back to the entry in the Craft control panel so they can review
     * the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $attributeAndFieldData
     * @return array<string, mixed>
     */
    public function __invoke(
        int $entryId,

        /** The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. This field is idempotent so setting a field here will replace all field contents with the provided field contents. If you are updating a field you must first get the field contents, update the content, and then pass the entire updated content here. */
        array $attributeAndFieldData = [],
    ): array
    {
        $entry = ($this->upsertEntry)(
            entryId: $entryId,
            attributeAndFieldData: $attributeAndFieldData,
        );

        return [
            '_notes' => 'The entry was successfully updated.',
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => $entry->getCpEditUrl(),
        ];
    }
}
