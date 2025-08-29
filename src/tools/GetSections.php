<?php

namespace happycog\craftmcp\tools;

use Craft;
use PhpMcp\Server\Attributes\McpTool;

class GetSections
{
    #[McpTool(
        name: 'get_sections',
        description: <<<'END'
        Get a list of sections and entry types in Craft CMS. This is helpful for creating new entries because
        you must pass a section ID and entry type ID when creating a new entry. This can also be helpful to
        orient yourself with the structure of the site.

        Each section can be customized with a unique set of custom fields. When pulling back section information
        you should also check the fields of that section to understand the schema of any data you send or receive.
        You can pass the `fieldLayoutId` to the `get_fields` tool to get a list of fields associated with that field
        layout.
        END
    )]
    public function get(): array
    {
        $sections = Craft::$app->getEntries()->getAllSections();

        $result = [];
        foreach ($sections as $section) {
            $entryTypes = [];
            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypes[] = [
                    'id' => $entryType->id,
                    'handle' => $entryType->handle,
                    'name' => $entryType->name,
                    'fieldLayoutId' => $entryType->fieldLayoutId,
                ];
            }

            $result[] = [
                'id' => $section->id,
                'handle' => $section->handle,
                'name' => $section->name,
                'type' => $section->type,
                'entryTypes' => $entryTypes,
            ];
        }

        return $result;
    }
}
