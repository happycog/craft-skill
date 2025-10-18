<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use Illuminate\Support\Collection;

class SearchContent
{
    /**
     * Search for content in the Craft CMS system. Returns a list of entries that match the search query, their
     * associated entryId, title, and URL for editing in the control panel.
     *
     * You can filter results by section using the sectionIds parameter, and optionally provide a search query.
     * Examples:
     * - Search within specific sections: provide both query and sectionIds
     * - Get all entries from sections: provide only sectionIds (no query)
     * - Search across all sections: provide only query (existing behavior)
     * - Get all entries: provide neither parameter
     *
     * @param array<int>|null $sectionIds
     * @return array{_notes: string, results: Collection<int, array{entryId: int, title: string, url: string}>}
     */
    public function search(
        ?string $query = null,
        int $limit = 5,

        /** The status of the entry to search for. By default only "live" entries are returned. You can also pass "pending", "expired" or "disabled" to get additional entries. */
        string $status = Entry::STATUS_LIVE,

        /** Optional array of section IDs to filter results. Only entries from these sections will be returned. */
        ?array $sectionIds = null
    ): array {
        // Validate section IDs if provided
        if ($sectionIds !== null) {
            foreach ($sectionIds as $sectionId) {
                $section = Craft::$app->getEntries()->getSectionById($sectionId);
                throw_unless($section, "Section with ID {$sectionId} not found");
            }
        }

        // Build the entry query
        $query_builder = Entry::find()->limit($limit)->status($status);

        // Apply section filtering if provided
        if ($sectionIds !== null) {
            $query_builder->sectionId($sectionIds);
        }

        // Apply search query if provided
        if ($query !== null) {
            $query_builder->search($query);
        }

        $result = $query_builder->all();

        // Generate appropriate notes message
        $notes = [];
        if ($query !== null) {
            $notes[] = "search query \"$query\"";
        }
        if ($sectionIds !== null) {
            $sectionNames = [];
            foreach ($sectionIds as $sectionId) {
                $section = Craft::$app->getEntries()->getSectionById($sectionId);
                if ($section !== null) {
                    $sectionNames[] = $section->name;
                }
            }
            $notes[] = "section(s): " . implode(', ', $sectionNames);
        }

        $notesText = empty($notes)
            ? 'The following entries were found.'
            : 'The following entries were found matching ' . implode(' and ', $notes) . '.';

        return [
            '_notes' => $notesText,
            'results' => Collection::make($result)->map(function (Entry $entry) {
                return [
                    'entryId' => (int) $entry->id,
                    'title' => (string) $entry->title,
                    'url' => ElementHelper::elementEditorUrl($entry),
                ];
            })
        ];
    }
}
