<?php

namespace happycog\craftmcp\tools;

use Craft;
use happycog\craftmcp\exceptions\ModelSaveException;

class DeleteEntryType
{
    /**
     * Delete an entry type from Craft CMS. This will remove the entry type and its associated field layout.
     *
     * **WARNING**: Deleting an entry type that has existing entries will cause data loss. The tool will
     * provide usage statistics and require confirmation for entry types with existing content.
     *
     * You _must_  get the user's approval before using the force parameter to delete entry types that have
     * existing entries. This action cannot be undone.
     *
     * @return array<string, mixed>
     */
    public function delete(
        /** The ID of the entry type to delete */
        int $entryTypeId,

        /** Force deletion even if entries exist (default: false) */
        bool $force = false
    ): array
    {
        $entriesService = Craft::$app->getEntries();

        // Get the entry type
        $entryType = $entriesService->getEntryTypeById($entryTypeId);
        if (!$entryType) {
            throw new \InvalidArgumentException("Entry type with ID {$entryTypeId} not found.");
        }

        // Store entry type info for response
        $entryTypeInfo = [
            'id' => $entryType->id,
            'name' => $entryType->name,
            'handle' => $entryType->handle,
            'fieldLayoutId' => $entryType->fieldLayoutId,
        ];

        // Check for existing entries
        $entryCount = $this->getEntryCount($entryType);
        $draftCount = $this->getDraftCount($entryType);
        $revisionCount = $this->getRevisionCount($entryType);

        $usageStats = [
            'entries' => $entryCount,
            'drafts' => $draftCount,
            'revisions' => $revisionCount,
            'total' => $entryCount + $draftCount + $revisionCount,
        ];

        // Check if deletion is safe
        if ($usageStats['total'] > 0 && !$force) {
            throw new \InvalidArgumentException(
                "Cannot delete entry type '{$entryType->name}' because it has {$usageStats['total']} associated items " .
                "({$entryCount} entries, {$draftCount} drafts, {$revisionCount} revisions). " .
                "Use force=true to delete anyway, but this will permanently remove all associated content."
            );
        }

        // Attempt to delete the entry type
        throw_unless($entriesService->deleteEntryType($entryType), ModelSaveException::class, $entryType);

        $message = "Entry type '{$entryTypeInfo['name']}' was successfully deleted.";
        if ($usageStats['total'] > 0) {
            $message .= " This removed {$usageStats['total']} associated items from the system.";
        }

        return [
            '_notes' => $message,
            'deleted' => true,
            'entryType' => $entryTypeInfo,
            'usageStats' => $usageStats,
            'forced' => $force && $usageStats['total'] > 0,
        ];
    }

    private function getEntryCount(\craft\models\EntryType $entryType): int
    {
        return (int) \craft\elements\Entry::find()
            ->typeId($entryType->id)
            ->status(null)
            ->count();
    }

    private function getDraftCount(\craft\models\EntryType $entryType): int
    {
        return (int) \craft\elements\Entry::find()
            ->typeId($entryType->id)
            ->drafts()
            ->count();
    }

    private function getRevisionCount(\craft\models\EntryType $entryType): int
    {
        return (int) \craft\elements\Entry::find()
            ->typeId($entryType->id)
            ->revisions()
            ->count();
    }
}
