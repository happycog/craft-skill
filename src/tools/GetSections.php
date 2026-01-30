<?php

namespace happycog\craftmcp\tools;

use Craft;
use happycog\craftmcp\actions\EntryTypeFormatter;

class GetSections
{
    public function __construct(
        protected EntryTypeFormatter $entryTypeFormatter,
    ) {
    }

    /**
     * Get a list of sections and entry types in Craft CMS. This is helpful for creating new entries because
     * you must pass a section ID and entry type ID when creating a new entry. This can also be helpful to
     * orient yourself with the structure of the site.
     *
     * Each section can be customized with a unique set of custom fields. When pulling back section information
     * you should also check the fields of that section to understand the schema of any data you send or receive.
     *
     * For large sites with many sections and fields, this endpoint will exclude field information to avoid
     * overwhelming the response. In such cases, use `sections/get {id}` to retrieve detailed field information
     * for a specific section.
     *
     * @param array<int>|null $sectionIds
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(
        /** Optional list of section IDs to limit results */
        ?array $sectionIds = null
    ): array
    {
        $sections = Craft::$app->getEntries()->getAllSections();

        // Determine if we should include field details based on site size
        $totalSections = count($sections);
        $totalFields = count(Craft::$app->getFields()->getAllFields('global'));
        $includeFields = $totalSections <= 10 && $totalFields <= 20;

        $result = [];
        foreach ($sections as $section) {
            if (is_array($sectionIds) && $sectionIds !== [] && !in_array($section->id, $sectionIds, true)) {
                continue;
            }

            $entryTypes = [];
            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypes[] = $this->entryTypeFormatter->formatEntryType($entryType, false, $includeFields);
            }

            $result[] = [
                'id' => $section->id,
                'handle' => $section->handle,
                'name' => $section->name,
                'type' => $section->type,
                'entryTypes' => $entryTypes,
            ];
        }

        // Add a note if fields were excluded
        if (!$includeFields) {
            $result[] = [
                'note' => 'Field information excluded for large sites. Use sections/get {id} to retrieve detailed field information for a specific section.',
            ];
        }

        return $result;
    }
}
