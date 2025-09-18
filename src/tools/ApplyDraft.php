<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class ApplyDraft
{
    #[McpTool(
        name: 'apply_draft',
        description: <<<'END'
        Apply a draft to its canonical entry, making the draft content live.
        
        This tool applies the changes from a draft to the canonical entry and removes the draft.
        The canonical entry will be updated with all the content from the draft.
        
        **Usage:**
        - Provide the draftId of the draft to apply
        - The draft must exist and be accessible
        - Works with both regular and provisional drafts
        - The draft will be removed after successful application
        
        **Returns:**
        - Updated canonical entry information
        - Entry details including title, slug, status
        - Section and entry type information
        - Control panel edit URL for review
        
        **Note:** This action cannot be undone. The draft content will replace the canonical entry content.
        
        After applying the draft always link the user back to the entry in the Craft control panel so they can review
        the changes in the context of the Craft UI.
        END
    )]
    public function apply(
        #[Schema(type: 'number', description: 'The draft ID to apply to its canonical entry')]
        int $draftId,
    ): array
    {
        // Find the draft by ID
        $draft = Entry::find()->id($draftId)->drafts()->one();
        if (!$draft) {
            // Check if the ID exists as a published entry
            $published = Entry::find()->id($draftId)->one();
            if ($published) {
                throw new \InvalidArgumentException("Entry with ID {$draftId} is not a draft. This tool can only be used with drafts.");
            }
            throw new \InvalidArgumentException("Draft with ID {$draftId} does not exist.");
        }
        
        // Verify this is actually a draft
        if (!$draft->getIsDraft()) {
            throw new \InvalidArgumentException("Entry with ID {$draftId} is not a draft. This tool can only be used with drafts.");
        }
        
        // Get the canonical entry before applying the draft
        $canonicalEntry = $draft->getCanonical(true);
        if (!$canonicalEntry) {
            throw new \RuntimeException("Unable to find canonical entry for draft {$draftId}.");
        }
        
        try {
            // Apply the draft using Craft's draft service
            $updatedEntry = Craft::$app->getDrafts()->applyDraft($draft);
            
            // Refresh the entry to get the latest data
            $updatedEntry = Entry::find()->id($updatedEntry->id)->one();
            
            return [
                '_notes' => 'The draft was successfully applied to the canonical entry.',
                'entryId' => $updatedEntry->id,
                'title' => $updatedEntry->title,
                'slug' => $updatedEntry->slug,
                'status' => $updatedEntry->status,
                'sectionId' => $updatedEntry->sectionId,
                'entryTypeId' => $updatedEntry->typeId,
                'siteId' => $updatedEntry->siteId,
                'postDate' => $updatedEntry->postDate?->format('c'),
                'dateUpdated' => $updatedEntry->dateUpdated?->format('c'),
                'url' => ElementHelper::elementEditorUrl($updatedEntry),
            ];
            
        } catch (\Throwable $e) {
            // Let Craft's validation errors pass through with meaningful messages
            throw new \RuntimeException('Failed to apply draft: ' . $e->getMessage());
        }
    }
}