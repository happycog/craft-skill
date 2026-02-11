<?php

namespace happycog\craftmcp\interfaces;

use craft\models\EntryType;
use craft\models\Section;

/**
 * Interface to abstract differences between Craft 4 and Craft 5 section/entry type services.
 *
 * In Craft 4, sections and entry types are managed by Craft::$app->getSections()
 * In Craft 5, sections and entry types are managed by Craft::$app->getEntries()
 */
interface SectionsServiceInterface
{
    public function saveSection(Section $section): bool;

    public function saveEntryType(EntryType $entryType): bool;

    public function deleteSection(Section $section): bool;

    public function getSectionById(int $id): ?Section;

    public function getSectionByHandle(string $handle): ?Section;

    /**
     * @return array<Section>
     */
    public function getAllSections(): array;

    public function getEntryTypeById(int $id): ?EntryType;

    public function getEntryTypeByHandle(string $handle): ?EntryType;

    /**
     * @return array<EntryType>
     */
    public function getAllEntryTypes(): array;

    /**
     * @return array<EntryType>
     */
    public function getEntryTypesBySectionId(int $sectionId): array;
}
