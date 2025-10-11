<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class DeleteSection
{
    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'delete_section',
        description: <<<'END'
        Delete a section from Craft CMS. This will remove the section and potentially affect related data.

        **WARNING**: Deleting a section that has existing entries will cause data loss. The tool will
        provide usage statistics and require confirmation for sections with existing content.

        You _must_ get the user's approval to use the force parameter to delete sections that have existing
        entries. This action cannot be undone.
        END
    )]
    public function delete(
        #[Schema(type: 'integer', description: 'The ID of the section to delete')]
        int $sectionId,

        #[Schema(type: 'boolean', description: 'Force deletion even if entries exist (default: false)')]
        bool $force = false
    ): array
    {
        $sectionsService = Craft::$app->getEntries();

        // Get the section
        $section = $sectionsService->getSectionById($sectionId);
        throw_unless($section, "Section with ID {$sectionId} not found");

        // Analyze impact before deletion
        $impact = $this->analyzeImpact($section);

        // Check if force is required
        if ($impact['hasContent'] && !$force) {
            // Type-safe access to impact data for string interpolation
            assert(is_int($impact['entryCount']) || is_string($impact['entryCount']));
            assert(is_int($impact['draftCount']) || is_string($impact['draftCount']));
            assert(is_int($impact['revisionCount']) || is_string($impact['revisionCount']));
            assert(is_int($impact['entryTypeCount']) || is_string($impact['entryTypeCount']));

            $entryCount = (string)$impact['entryCount'];
            $draftCount = (string)$impact['draftCount'];
            $revisionCount = (string)$impact['revisionCount'];
            $entryTypeCount = (string)$impact['entryTypeCount'];

            throw new \RuntimeException(
                "Section '{$section->name}' contains data and cannot be deleted without force=true.\n\n" .
                "Impact Assessment:\n" .
                "- Entries: {$entryCount}\n" .
                "- Drafts: {$draftCount}\n" .
                "- Revisions: {$revisionCount}\n" .
                "- Entry Types: {$entryTypeCount}\n\n" .
                "Set force=true to proceed with deletion. This action cannot be undone."
            );
        }

        // Store section info for response
        $sectionInfo = [
            'id' => $section->id,
            'name' => $section->name,
            'handle' => $section->handle,
            'type' => $section->type,
            'impact' => $impact
        ];

        // Delete the section
        throw_unless($sectionsService->deleteSection($section), ModelSaveException::class, $section);

        return $sectionInfo;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeImpact(\craft\models\Section $section): array
    {
        // Count entries
        $entryCount = Entry::find()
            ->sectionId($section->id)
            ->count();

        // Count drafts
        $draftCount = Entry::find()
            ->sectionId($section->id)
            ->drafts()
            ->count();

        // Count revisions
        $revisionCount = Entry::find()
            ->sectionId($section->id)
            ->revisions()
            ->count();

        // Count entry types
        $entryTypes = $section->getEntryTypes();
        $entryTypeCount = count($entryTypes);

        // Check if there's any content
        $hasContent = $entryCount > 0 || $draftCount > 0 || $revisionCount > 0;

        return [
            'hasContent' => $hasContent,
            'entryCount' => $entryCount,
            'draftCount' => $draftCount,
            'revisionCount' => $revisionCount,
            'entryTypeCount' => $entryTypeCount,
            'entryTypes' => array_map(function($et) {
                return [
                    'id' => $et->id,
                    'name' => $et->name,
                    'handle' => $et->handle
                ];
            }, $entryTypes)
        ];
    }
}
