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
     * - An "Entry" in Craft is a generic term. Entries could hold categories, media, and a variety of other data types.
     * - You should query the sections to get the types of entries that can be updated. Always use the section type and
     * section definition to determine if the user is requesting an "Entry".
     * - When updating a new entry pass an integer `$sectionId` and an integer `$entryTypeId`. You can use other tools
     * to determine the appropriate IDs to use.
     * - Attribute and field data can be passed native attributes like title, slug, postDate, etc. as well as any
     * custom fields that are associated with the entry type. You can look up custom field handles with a separate tool
     * call.
     * - The attribute and field data is a JSON object keyed by the field handle. For example, a body field would be
     * set by passing {"body":"This is the body content"}. And if you pass multiple fields like a title and body field
     * like {"title":"This is the title","body":"This is the body content"}
     * - You should prefer creating a Draft instead of updating an entry in place. You can do this with the CreateDraft
     * tool and its corresponding UpdateDraft tool. This way the user can reciew their changes in thr Craft UI before
     * accepting them.
     *
     * After updating the entry always link the user back to the entry in the Craft control panel so they can review
     * the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $attributeAndFieldData
     * @return array<string, mixed>
     */
    public function update(
        int $entryId,

        /** The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. This field is idempotent so setting a field here will replace all field contents with the provided field contents. If you are updating a field you must first get the field contents, update the content, and then pass the entire updated content here. */
        array $attributeAndFieldData = [],
    ): array
    {
        $entry = ($this->upsertEntry)(
            entryId: $entryId,
            attributeAndFieldData: $attributeAndFieldData,
        );

        $url = ElementHelper::elementEditorUrl($entry);

        return [
            '_notes' => 'The entry was successfully updated.',
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($entry),
        ];
    }
}
