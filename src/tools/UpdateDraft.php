<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use happycog\craftmcp\exceptions\ModelSaveException;

class UpdateDraft
{
    /**
     * Update an existing draft's content and metadata by draftId.
     *
     * Works with both regular and provisional drafts without distinction.
     * Supports the same field and attribute updates as UpdateEntry.
     * Uses PATCH semantics - only updates fields that are provided, preserving existing data.
     *
     * You can update:
     * - Entry content fields (title, body, custom fields, etc.)
     * - Draft-specific properties (draftName, draftNotes)
     * - Native entry attributes (title, slug, postDate, etc.)
     *
     * The attribute and field data is a JSON object keyed by the field handle.
     * For example, to update a body field: {"body":"Updated content"}
     * To update multiple fields: {"title":"New title","body":"Updated body"}
     *
     * Returns updated draft information including edit URL for the Craft control panel.
     *
     * @param array<string, mixed> $attributeAndFieldData
     * @return array<string, mixed>
     */
    public function update(
        /** The draft ID to update */
        int $draftId,

        /** The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. Uses PATCH semantics - only provided fields are updated, others are preserved. */
        array $attributeAndFieldData = [],

        /** Update the draft name */
        ?string $draftName = null,

        /** Update the draft notes */
        ?string $draftNotes = null,
    ): array
    {
        // Find the draft by ID
        $draft = Entry::find()->id($draftId)->drafts()->one();
        if (!$draft instanceof Entry) {
            // Check if the ID exists as a published entry
            $published = Entry::find()->id($draftId)->one();
            if ($published instanceof Entry) {
                throw new \InvalidArgumentException("Entry with ID {$draftId} is not a draft. Use update_entry for published entries.");
            }
            throw new \InvalidArgumentException("Entry with ID {$draftId} does not exist.");
        }
        
        // Verify this is actually a draft (redundant check but good for clarity)
        if (!$draft->getIsDraft()) {
            throw new \InvalidArgumentException("Entry with ID {$draftId} is not a draft. Use update_entry for published entries.");
        }
        
        // Update draft metadata if provided
        if ($draftName !== null) {
            $draft->draftName = $draftName;
        }
        
        if ($draftNotes !== null) {
            $draft->draftNotes = $draftNotes;
        }
        
        // Update field and attribute data using PATCH semantics
        if (!empty($attributeAndFieldData)) {
            $customFields = collect($draft->getFieldLayout()?->getCustomFields() ?? [])->keyBy('handle')->toArray();

            foreach ($attributeAndFieldData as $key => $value) {
                if (isset($customFields[$key])) {
                    // This is a custom field
                    $draft->setFieldValue($key, $value);
                } else {
                    // This is a native attribute
                    $draft->$key = $value;
                }
            }
        }

        // Save the updated draft
        throw_unless(Craft::$app->getElements()->saveElement($draft), ModelSaveException::class, $draft);

        return [
            '_notes' => 'The draft was successfully updated.',
            'draftId' => $draft->id,
            'canonicalId' => $draft->canonicalId,
            'title' => $draft->title,
            'slug' => $draft->slug,
            'draftName' => $draft->getIsDraft() ? $draft->draftName : null,
            'draftNotes' => $draft->getIsDraft() ? $draft->draftNotes : null,
            'provisional' => $draft->getIsDraft() ? $draft->isProvisionalDraft : false,
            'siteId' => $draft->siteId,
            'postDate' => $draft->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($draft),
        ];
    }
}