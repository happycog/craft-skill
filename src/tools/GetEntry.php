<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;

class GetEntry
{
    /**
     * Get entry details.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        int $entryId,
    ): array
    {
        $entry = Craft::$app->getElements()->getElementById($entryId, Entry::class);
        
        throw_unless($entry instanceof Entry, \InvalidArgumentException::class, "Entry with ID {$entryId} not found");

        return $entry->toArray();
    }
}
