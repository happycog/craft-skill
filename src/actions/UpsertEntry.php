<?php

namespace happycog\craftmcp\actions;

use Craft;
use craft\elements\Entry;
use Illuminate\Support\Collection;
use happycog\craftmcp\actions\normalizers\SectionIdOrHandleToSectionId;

class UpsertEntry
{
    /**
     * @param array<string, mixed> $attributeAndFieldData
     */
    public function __invoke(
        ?int $entryId=null,
        ?int $sectionId=null,
        ?int $entryTypeId=null,
        ?int $siteId=null,
        array $attributeAndFieldData = [],
    ): Entry {
        if ($entryId) {
            $entry = Craft::$app->getElements()->getElementById($entryId, Entry::class);
            throw_unless($entry, "Entry with ID {$entryId} not found");
        }
        else {
            $entry = new Entry();
            throw_unless($sectionId, 'sectionId is required for new entries');
            throw_unless($entryTypeId, 'entryTypeId is required for new entries');
            $entry->sectionId = $sectionId;
            $entry->typeId = $entryTypeId;

            // Set siteId for new entries only (don't change existing entry's site)
            if ($siteId) {
                $entry->siteId = $siteId;
            }
        }

        $fieldLayout = $entry->getFieldLayout();
        throw_unless($fieldLayout, 'Entry field layout not found');

        /** @phpstan-ignore-next-line */
        $customFields = Collection::make($fieldLayout->getCustomFields())
            ->keyBy('handle')
            ->toArray();

        foreach ($attributeAndFieldData as $key => $value) {
            ($customFields[$key] ?? null)
                ? $entry->setFieldValue($key, $value)
                : $entry->$key = $value;
        }

        throw_unless(Craft::$app->getElements()->saveElement($entry), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
