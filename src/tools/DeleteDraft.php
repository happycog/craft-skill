<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;

class DeleteDraft
{
    /**
     * Delete a draft in Craft without changing its canonical entry.
     *
     * By default, this performs a soft delete on the draft only. Set `permanentlyDelete`
     * to true to permanently remove the draft.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** The draft ID to delete. */
        int $draftId,

        /** Set to true to permanently delete the draft. Default is false (soft delete). */
        bool $permanentlyDelete = false,
    ): array {
        $draft = Entry::find()->id($draftId)->drafts()->one();
        if (!$draft instanceof Entry) {
            $published = Entry::find()->id($draftId)->one();
            if ($published instanceof Entry) {
                throw new \InvalidArgumentException("Entry with ID {$draftId} is not a draft. This tool can only be used with drafts.");
            }

            throw new \InvalidArgumentException("Draft with ID {$draftId} does not exist.");
        }

        throw_unless($draft->getIsDraft(), \InvalidArgumentException::class, "Entry with ID {$draftId} is not a draft. This tool can only be used with drafts.");

        $response = [
            '_notes' => 'The draft was successfully deleted. The canonical entry was left unchanged.',
            'draftId' => $draft->id,
            'canonicalId' => $draft->canonicalId,
            'title' => $draft->title,
            'slug' => $draft->slug,
            'draftName' => $draft->draftName,
            'draftNotes' => $draft->draftNotes,
            'provisional' => $draft->isProvisionalDraft,
            'siteId' => $draft->siteId,
            'deletedPermanently' => $permanentlyDelete,
        ];

        throw_unless(Craft::$app->getElements()->deleteElement($draft, $permanentlyDelete), "Failed to delete draft with ID {$draftId}.");

        return $response;
    }
}
