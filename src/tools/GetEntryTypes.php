<?php

declare(strict_types=1);

namespace happycog\craftmcp\tools;

use Craft;
use happycog\craftmcp\actions\EntryTypeFormatter;

class GetEntryTypes
{
    public function __construct(
        protected EntryTypeFormatter $entryTypeFormatter,
    ) {
    }


    /**
     * Get a list of entry types with complete field information, usage stats, and edit URLs.
     *
     * @param array<int>|null $entryTypeIds
     * @return array<int, array<string, mixed>>
     */
    public function getAll(
        /** Optional list of entry type IDs to limit results */
        ?array $entryTypeIds = null
    ): array
    {
        $entriesService = Craft::$app->getEntries();

        $results = [];
        foreach ($entriesService->getAllEntryTypes() as $entryType) {
            if (is_array($entryTypeIds) && $entryTypeIds !== [] && !in_array($entryType->id, $entryTypeIds, true)) {
                continue;
            }
            $results[] = $this->entryTypeFormatter->formatEntryType($entryType, true);
        }

        return $results;
    }
}
