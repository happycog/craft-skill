<?php

namespace happycog\craftmcp\tools;

use Composer\Semver\Semver;
use Craft;
use craft\models\Section;
use happycog\craftmcp\actions\EntryTypeFormatter;
use happycog\craftmcp\interfaces\SectionsServiceInterface;
use function happycog\craftmcp\helpers\service;

class GetSection
{
    public function __construct(
        protected EntryTypeFormatter $entryTypeFormatter,
    ) {
    }

    /**
     * Get detailed information about a specific section including all entry types and their fields.
     *
     * This tool returns comprehensive section details including all custom fields for each entry type,
     * which is useful when you need the complete schema for a section. For an overview of all sections
     * without field details, use `sections/list` instead.
     *
     * After retrieving section information, you can use the section ID and entry type ID to create
     * new entries with `entries/create`.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        /** ID of the section to retrieve */
        int $sectionId
    ): array
    {
        $sectionsService = service(SectionsServiceInterface::class);
        $section = $sectionsService->getSectionById($sectionId);

        if (!$section instanceof Section) {
            throw new \InvalidArgumentException("Section with ID {$sectionId} not found");
        }

        $entryTypes = [];
        foreach ($section->getEntryTypes() as $entryType) {
            // Include all field information for this detailed view
            $entryTypes[] = $this->entryTypeFormatter->formatEntryType($entryType, false, true);
        }

        $result = [
            'id' => $section->id,
            'handle' => $section->handle,
            'name' => $section->name,
            'type' => $section->type,
            'enableVersioning' => $section->enableVersioning,
            'previewTargets' => $section->previewTargets,
            'entryTypes' => $entryTypes,
        ];

        // In Craft 5, propagationMethod is an enum with a value property
        // In Craft 4, it's a string
        if (Semver::satisfies(Craft::$app->getVersion(), '~5.0')) {
            $result['propagationMethod'] = $section->propagationMethod->value;
        } else {
            $result['propagationMethod'] = $section->propagationMethod;
        }

        return $result;
    }
}
