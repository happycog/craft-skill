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
     * CRITICAL - CLI USAGE REQUIREMENTS:
     * - DO NOT use --attributeAndFieldData with JSON blobs
     * - ALWAYS use direct field flags: --title="value" --body="content" --customField="data"
     * - For arrays use escaped brackets: --tags\[\]=news --tags\[\]=featured
     * - For nested data use bracket notation: --matrix[0][type]="text" --matrix[0][fields][body]="content"
     * - This is REQUIRED for debugging and maintainability
     *
     * MATRIX FIELDS - IMPORTANT:
     * - Matrix fields are ALWAYS replaced entirely (idempotent operation)
     * - You MUST pass the WHOLE matrix field contents, not just changes
     * - To preserve existing blocks: First get the entry, then include ALL blocks (existing + new) in the update
     * - Each block must use keys: new1, new2, new3, etc. (NOT numeric indices, even for existing blocks)
     * - Each block needs: [type] and [fields][fieldHandle] for each field in that block
     * - Example: --matrixField\[new1\]\[type\]=text --matrixField\[new1\]\[fields\]\[body\]="content"
     *           --matrixField\[new2\]\[type\]=image --matrixField\[new2\]\[fields\]\[image\]\[\]=123
     *
     * Example (CORRECT):
     * agent-craft entries/update 123 --title="Updated Title" --body="New content" --author="Jane"
     *
     * Example with Matrix - Replacing ALL blocks (CORRECT):
     * agent-craft entries/update 123 --title="Test" \
     *   --blocks\[new1\]\[type\]=textBlock --blocks\[new1\]\[fields\]\[heading\]="Intro" \
     *   --blocks\[new2\]\[type\]=imageBlock --blocks\[new2\]\[fields\]\[image\]\[\]=456
     *
     * Example (INCORRECT - DO NOT DO THIS):
     * agent-craft entries/update 123 --attributeAndFieldData='{"title":"Updated Title",...}'
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
