<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\helpers\ElementHelper;
use happycog\craftmcp\actions\UpsertEntry;

class CreateEntry
{
    public function __construct(
        protected UpsertEntry $upsertEntry,
    ) {
    }

    /**
     * Create an entry in Craft.
     *
     * - An "Entry" in Craft is a generic term. Entries could hold categories, media, and a variety of other data types.
     * - You should query the sections to get the types of entries that can be created. Always use the section type and
     * section definition to determine if the user is requesting an "Entry".
     * - When creating a new entry pass an integer `$sectionId` and an integer `$entryTypeId`. You can use other tools
     * to determine the appropriate IDs to use.
     * - Attribute and field data can be passed native attributes like title, slug, postDate, etc. as well as any
     * custom fields that are associated with the entry type. You can look up custom field handles with a separate tool
     * call.
     * - The attribute and field data is a JSON object keyed by the field handle. For example, a body field would be
     * set by passing {"body":"This is the body content"}. And if you pass multiple fields like a title and body field
     * like {"title":"This is the title","body":"This is the body content"}
     * - siteId: Optional site ID for multi-site installations. Defaults to primary site if not provided.
     * Use the GetSites tool to discover valid siteId values.
     *
     * After creating the entry always link the user back to the entry in the Craft control panel so they can review
     * the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $attributeAndFieldData
     * @return array{_notes: string, entryId: int|null, title: string|null, slug: string|null, postDate: string|null, url: string}
     */
    public function create(
        int $sectionId,
        int $entryTypeId,

        /** Site ID, defaults to primary site */
        ?int $siteId = null,

        /** The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. This field is idempotent so setting a field here will replace all field contents with the provided field contents. */
        array $attributeAndFieldData = [],
    ): array
    {
        // Set default site if not provided
        $siteId = $siteId ?? Craft::$app->getSites()->getPrimarySite()->id;
        throw_unless(is_int($siteId), 'Failed to determine valid site ID');

        // Validate site exists
        $site = Craft::$app->getSites()->getSiteById((int) $siteId);
        throw_unless($site, \InvalidArgumentException::class, "Site with ID {$siteId} does not exist.");

        $entry = ($this->upsertEntry)(
            sectionId: $sectionId,
            entryTypeId: $entryTypeId,
            siteId: $siteId,
            attributeAndFieldData: $attributeAndFieldData,
        );        return [
            '_notes' => 'The entry was successfully created.',
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($entry),
        ];
    }
}
