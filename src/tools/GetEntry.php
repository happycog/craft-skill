<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class GetEntry
{
    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get_entry',
        description: <<<'END'
        Get entry details.
        END
    )]
    public function get(
        #[Schema(type: 'number')]
        int $entryId,
    ): array
    {
        $entry = Craft::$app->getElements()->getElementById($entryId, Entry::class);
        
        if (!$entry instanceof Entry) {
            throw new \InvalidArgumentException("Entry with ID {$entryId} not found");
        }

        return $entry->toArray();
    }
}
