<?php

namespace happycog\craftmcp\tools;

use Craft;
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
        $entry = Craft::$app->getElements()->getElementById($entryId);

        return $entry->toArray();
    }
}
