<?php

namespace markhuot\craftmcp\actions;

use Craft;
use craft\elements\Entry;
use markhuot\craftmcp\actions\normalizers\SectionIdOrHandleToSectionId;

class UpsertEntry
{
    public function __invoke(
        ?int $entryId=null,
        ?int $sectionId=null,
        ?int $entryTypeId=null,
        array $attributeAndFieldData = [],
    ): Entry {
        if ($entryId) {
            $entry = Craft::$app->getElements()->getElementById($entryId);
        }
        else {
            $entry = new Entry();
            $entry->sectionId = $sectionId;
            $entry->typeId = $entryTypeId;
        }

        $customFields = collect($entry->getFieldLayout()->getCustomFields())
            ->keyBy('handle')
            ->toArray();

        foreach ($attributeAndFieldData as $key => $value) {
            ($customFields[$key] ?? null)
                ? $entry->setFieldValue($key, $value)
                : $entry->$key = $value;
        }

        if (! Craft::$app->getElements()->saveElement($entry)) {
            throw new \RuntimeException(implode(' ', $entry->getErrorSummary(true)));
        }

        return $entry;
    }
}
