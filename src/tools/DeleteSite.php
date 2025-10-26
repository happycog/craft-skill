<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use happycog\craftmcp\exceptions\ModelSaveException;

class DeleteSite
{
    /**
     * Delete a site from Craft CMS. This will remove the site and potentially affect related data.
     *
     * **WARNING**: Deleting a site that has existing entries will cause data loss. The tool will
     * provide usage statistics and require confirmation for sites with existing content.
     *
     * You _must_ get the user's approval to use the force parameter to delete sites that have existing
     * entries. This action cannot be undone.
     *
     * **IMPORTANT**: You cannot delete the primary site. If you need to delete it, first set another
     * site as primary using the UpdateSite tool.
     *
     * @return array<string, mixed>
     */
    public function delete(
        /** The ID of the site to delete */
        int $siteId,

        /** Force deletion even if entries exist (default: false) */
        bool $force = false
    ): array
    {
        $sitesService = Craft::$app->getSites();

        // Get the site
        $site = $sitesService->getSiteById($siteId);
        throw_unless($site, "Site with ID {$siteId} not found");

        // Prevent deletion of primary site
        throw_if($site->primary, "Cannot delete the primary site. Set another site as primary first.");

        // Analyze impact before deletion
        $impact = $this->analyzeImpact($site);

        // Check if force is required
        if ($impact['hasContent'] && !$force) {
            // Type-safe access to impact data for string interpolation
            assert(is_int($impact['entryCount']) || is_string($impact['entryCount']));
            assert(is_int($impact['draftCount']) || is_string($impact['draftCount']));
            assert(is_int($impact['revisionCount']) || is_string($impact['revisionCount']));

            $entryCount = (string)$impact['entryCount'];
            $draftCount = (string)$impact['draftCount'];
            $revisionCount = (string)$impact['revisionCount'];

            throw new \RuntimeException(
                "Site '{$site->name}' contains data and cannot be deleted without force=true.\n\n" .
                "Impact Assessment:\n" .
                "- Entries: {$entryCount}\n" .
                "- Drafts: {$draftCount}\n" .
                "- Revisions: {$revisionCount}\n\n" .
                "Set force=true to proceed with deletion. This action cannot be undone."
            );
        }

        // Store site info for response
        $siteInfo = [
            'id' => $site->id,
            'name' => $site->name,
            'handle' => $site->handle,
            'language' => $site->language,
            'baseUrl' => $site->getBaseUrl(),
            'impact' => $impact
        ];

        // Delete the site
        throw_unless($sitesService->deleteSite($site), ModelSaveException::class, $site);

        return $siteInfo;
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeImpact(\craft\models\Site $site): array
    {
        // Count entries for this site
        $entryCount = Entry::find()
            ->siteId($site->id)
            ->count();

        // Count drafts for this site
        $draftCount = Entry::find()
            ->siteId($site->id)
            ->drafts()
            ->count();

        // Count revisions for this site
        $revisionCount = Entry::find()
            ->siteId($site->id)
            ->revisions()
            ->count();

        // Check if there's any content
        $hasContent = $entryCount > 0 || $draftCount > 0 || $revisionCount > 0;

        return [
            'hasContent' => $hasContent,
            'entryCount' => $entryCount,
            'draftCount' => $draftCount,
            'revisionCount' => $revisionCount,
        ];
    }
}
