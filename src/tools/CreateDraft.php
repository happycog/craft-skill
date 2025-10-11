<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;
use craft\helpers\ElementHelper;
use happycog\craftmcp\exceptions\ModelSaveException;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class CreateDraft
{
    public function __construct(
        protected \happycog\craftmcp\actions\UpsertEntry $upsertEntry,
    ) {
    }

    /**
     * @param array<string, mixed> $attributeAndFieldData
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'create_draft',
        description: <<<'END'
        Create a draft in Craft CMS either from scratch or from an existing published entry.

        Drafts allow content creators to work on changes without affecting live content and save work in progress.

        **Creating from scratch:**
        - Provide sectionId and entryTypeId (similar to CreateEntry)
        - Optionally provide initial field data

        **Creating from existing entry:**
        - Provide canonicalId (the ID of the published entry to create a draft from)
        - The draft inherits the canonical entry's content
        - Optionally provide field data to override specific fields

        **Draft options:**
        - draftName: Optional name for the draft (defaults to auto-generated name)
        - draftNotes: Optional notes about the draft
        - provisional: Set to true for provisional drafts (auto-save drafts), defaults to false
        - siteId: Optional site ID, defaults to primary site

        Returns draft information including ID and edit URL for the Craft control panel.
        END
    )]
    public function create(
        #[Schema(type: 'number', description: 'Section ID when creating from scratch')]
        ?int $sectionId = null,

        #[Schema(type: 'number', description: 'Entry type ID when creating from scratch')]
        ?int $entryTypeId = null,

        #[Schema(type: 'number', description: 'Canonical entry ID when creating from existing entry')]
        ?int $canonicalId = null,

        #[Schema(type: 'string', description: 'Optional draft name')]
        ?string $draftName = null,

        #[Schema(type: 'string', description: 'Optional draft notes')]
        ?string $draftNotes = null,

        #[Schema(type: 'boolean', description: 'Whether to create a provisional draft (auto-save draft)')]
        bool $provisional = false,

        #[Schema(type: 'number', description: 'Site ID, defaults to primary site')]
        ?int $siteId = null,

        #[Schema(type: 'object', description: 'Initial field data for the draft')]
        array $attributeAndFieldData = [],
    ): array
    {
        // Validate input parameters
        if ($canonicalId && ($sectionId || $entryTypeId)) {
            throw new \InvalidArgumentException('Cannot specify both canonicalId and sectionId/entryTypeId. Use canonicalId for drafts from existing entries, or sectionId/entryTypeId for new drafts.');
        }

        if (!$canonicalId && (!$sectionId || !$entryTypeId)) {
            throw new \InvalidArgumentException('Must specify either canonicalId (for draft from existing entry) or both sectionId and entryTypeId (for new draft).');
        }

        // Set default site if not provided
        $siteId ??= Craft::$app->getSites()->getPrimarySite()->id;

        // Validate site exists
        if ($siteId !== null) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            throw_unless($site, \InvalidArgumentException::class, "Site with ID {$siteId} does not exist.");
        }

        if ($canonicalId) {
            // Create draft from existing entry
            $canonicalEntry = Entry::find()->id($canonicalId)->one();
            if (!$canonicalEntry instanceof Entry) {
                throw new \InvalidArgumentException("Entry with ID {$canonicalId} does not exist.");
            }

            // Create draft using Craft's draft service
            $draft = Craft::$app->getDrafts()->createDraft(
                $canonicalEntry,
                null, // creator (null = current user)
                $draftName,
                $draftNotes,
                [], // newAttributes
                $provisional
            );

            // Override site if different from canonical
            if ($draft->siteId !== $siteId) {
                $draft->siteId = $siteId;
            }
        } else {
            // For from-scratch drafts, we need to create a regular entry first as Craft's
            // draft system requires a canonical element. This is the standard approach.

            // Create the canonical entry first
            $canonicalEntry = ($this->upsertEntry)(
                sectionId: $sectionId,
                entryTypeId: $entryTypeId,
                attributeAndFieldData: $attributeAndFieldData
            );            // Now create a draft from this canonical entry
            $draft = Craft::$app->getDrafts()->createDraft(
                $canonicalEntry,
                null, // creator
                $draftName,
                $draftNotes,
                [], // newAttributes
                $provisional
            );

            // Clear the attributeAndFieldData since we already applied it
            $attributeAndFieldData = [];
        }

        // Apply additional field data if provided (for updates from canonical)
        if (!empty($attributeAndFieldData)) {
            $fieldLayout = $draft->getFieldLayout();
            if ($fieldLayout) {
                $customFields = collect($fieldLayout->getCustomFields())
                    ->keyBy('handle')
                    ->toArray();

                foreach ($attributeAndFieldData as $key => $value) {
                    ($customFields[$key] ?? null)
                        ? $draft->setFieldValue($key, $value)
                        : $draft->$key = $value;
                }

                // Save the updated draft
                throw_unless(Craft::$app->getElements()->saveElement($draft), ModelSaveException::class, $draft);
            }
        }

        return [
            '_notes' => 'The draft was successfully created.',
            'draftId' => $draft->id,
            'canonicalId' => $draft->canonicalId,
            'title' => $draft->title,
            'slug' => $draft->slug,
            'draftName' => $draft->getIsDraft() ? $draft->draftName : $draftName,
            'draftNotes' => $draft->getIsDraft() ? $draft->draftNotes : $draftNotes,
            'provisional' => $draft->getIsDraft() ? $draft->isProvisionalDraft : $provisional,
            'siteId' => $draft->siteId,
            'url' => ElementHelper::elementEditorUrl($draft),
        ];
    }
}
