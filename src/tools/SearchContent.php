<?php

namespace happycog\craftmcp\tools;

use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use Illuminate\Support\Collection;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class SearchContent
{
    /**
     * @return array{_notes: string, results: Collection<int, array{entryId: int, title: string, url: string}>}
     */
    #[McpTool(
        name: 'search_content',
        description: <<<'END'
        Search for content in the Craft CMS system. Returns a list of entries that match the search query, their
        associated entryId, title, and URL for editing in the control panel.
        END
    )]
    public function search(
        string $query,
        int $limit=5,
        #[Schema(description: 'The status of the entry to search for. By default only "live" entries are returned. You can also pass "pending", "expired" or "disabled" to get additional entries.')]
        string $status=Entry::STATUS_LIVE
    ): array {
        $result = Entry::find()->search($query)->limit($limit)->status($status)->all();

        return [
            '_notes' => 'The following entries were found matching the search query of "' . $query . '".',
            'results' => collect($result)->map(function (Entry $entry) {
                return [
                    'entryId' => $entry->id,
                    'title' => $entry->title,
                    'url' => ElementHelper::elementEditorUrl($entry),
                ];
            })
        ];
    }
}
