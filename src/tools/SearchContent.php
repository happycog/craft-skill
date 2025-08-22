<?php

namespace markhuot\craftmcp\tools;

use craft\elements\Entry;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use Illuminate\Support\Collection;
use PhpMcp\Server\Attributes\McpTool;

class SearchContent
{
    #[McpTool(
        name: 'search_content',
        description: <<<'END'
        Search for content in the Craft CMS system. Returns a list of entries that match the search query, their
        associated entryId, title, and URL for editing in the control panel.
        END
    )]
    public function search(string $query, int $limit=5, string $status=Entry::STATUS_LIVE): Collection
    {
        $result = Entry::find()->search($query)->limit(5)->status($status)->all();

        return collect($result)->map(function (Entry $entry) {
            return [
                'entryId' => $entry->id,
                'title' => $entry->title,
                'url' => ElementHelper::elementEditorUrl($entry),
            ];
        });
    }
}
