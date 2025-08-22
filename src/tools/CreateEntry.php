<?php

namespace markhuot\craftmcp\tools;

use Craft;
use craft\helpers\ElementHelper;
use markhuot\craftmcp\actions\UpsertEntry;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;

class CreateEntry
{
    /**
     * @return array{entryId: int, title: string, slug: string, postDate: string, url: string}
     */
    #[McpTool(
        name: 'create_entry',
        description: <<<'END'
        Create an entry in Craft.
        
        - When creating a new entry pass an integer `$sectionId` and an integer `$entryTypeId`. You can use other tools
        to determine the appropriate IDs to use.
        - Attribute and field data can be passed native attributes like title, slug, postDate, etc. as well as any
        custom fields that are associated with the entry type. You can look up custom field handles with a separate tool
        call.
        - The attribute and field data is a JSON object keyed by the field handle. For example, a body field would be
        set by passing {"body":"This is the body content"}. And if you pass multiple fields like a title and body field
        like {"title":"This is the title","body":"This is the body content"}
        
        After creating the entry always link the user back to the entry in the Craft control panel so they can review
        the changes in the context of the Craft UI.
        END
    )]
    public function create(
        #[Schema(type: 'number')]
        int $sectionId,
        #[Schema(type: 'number')]
        int $entryTypeId,

        #[Schema(type: 'object', description: 'The attribute and field data keyed by the handle. For example, to set the body to "foo" you would pass {"body":"foo"}. This field is idempotent so setting a field here will replace all field contents with the provided field contents.')]
        array $attributeAndFieldData = [],
    ): array
    {
        $upsertEntry = Craft::$container->get(UpsertEntry::class);

        $entry = $upsertEntry(
            sectionId: $sectionId,
            entryTypeId: $entryTypeId,
            attributeAndFieldData: $attributeAndFieldData,
        );

        return [
            'entryId' => $entry->id,
            'title' => $entry->title,
            'slug' => $entry->slug,
            'postDate' => $entry->postDate?->format('c'),
            'url' => ElementHelper::elementEditorUrl($entry),
        ];
    }
}
