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
     * CRITICAL - CLI USAGE REQUIREMENTS:
     * - DO NOT use --attributeAndFieldData with JSON blobs
     * - ALWAYS use direct field flags: --title="value" --body="content" --customField="data"
     * - For arrays use escaped brackets: --categories\[\]=1 --categories\[\]=2
     * - For nested data use bracket notation: --matrix[0][type]="text" --matrix[0][fields][body]="content"
     * - This is REQUIRED for debugging and maintainability
     *
     * MATRIX FIELDS:
     * - Matrix fields are ALWAYS replaced entirely (idempotent operation)
     * - Each block must use keys: new1, new2, new3, etc. (NOT numeric indices)
     * - Each block needs: [type] and [fields][fieldHandle] for each field in that block
     * - Example: --matrixField\[new1\]\[type\]=text --matrixField\[new1\]\[fields\]\[body\]="content"
     *           --matrixField\[new2\]\[type\]=image --matrixField\[new2\]\[fields\]\[image\]\[\]=123
     *
     * Example (CORRECT):
     * agent-craft entries/create --sectionId=1 --entryTypeId=2 --title="My Entry" --body="Content" --author="John"
     *
     * Example with Matrix (CORRECT):
     * agent-craft entries/create --sectionId=1 --entryTypeId=2 --title="Test" \
     *   --blocks\[new1\]\[type\]=textBlock --blocks\[new1\]\[fields\]\[heading\]="Intro" \
     *   --blocks\[new2\]\[type\]=imageBlock --blocks\[new2\]\[fields\]\[image\]\[\]=456
     *
     * Example (INCORRECT - DO NOT DO THIS):
     * agent-craft entries/create --sectionId=1 --entryTypeId=2 --attributeAndFieldData='{"title":"My Entry",...}'
     *
     * Entry Information:
     * - An "Entry" in Craft is a generic term. Entries could hold categories, media, and a variety of other data types.
     * - Query sections first to get the types of entries that can be created. Use the section type and definition to
     * determine if the user is requesting an "Entry".
     * - Pass integer `$sectionId` and integer `$entryTypeId`. Use other tools to determine the appropriate IDs.
     * - siteId: Optional site ID for multi-site installations. Defaults to primary site if not provided.
     * Use the GetSites tool to discover valid siteId values.
     *
     * After creating the entry always link the user back to the entry in the Craft control panel so they can review
     * the changes in the context of the Craft UI.
     *
     * @param array<string, mixed> $attributeAndFieldData
     * @return array{_notes: string, entryId: int|null, title: string|null, slug: string|null, postDate: string|null, url: string}
     */
    public function __invoke(
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
        );

        return [
            '_notes' => 'The entry was successfully created.',
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($entry),
        ];
    }
}
