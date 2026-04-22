<?php

namespace happycog\craftmcp\tools;

use Craft;
use craft\elements\Entry;

class GetEntry
{
    /**
     * Get entry details by ID, URI, or slug.
     *
     * Provide exactly one identifier. `uri` and `slug` are expected to uniquely identify
     * an entry in the current Craft installation.
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        ?int $entryId = null,
        ?string $uri = null,
        ?string $slug = null,
    ): array
    {
        $provided = array_filter([
            'entryId' => $entryId,
            'uri' => $uri,
            'slug' => $slug,
        ], static fn(mixed $value): bool => $value !== null);

        throw_unless(
            count($provided) === 1,
            \InvalidArgumentException::class,
            'Provide exactly one of entryId, uri, or slug.'
        );

        $entry = match (true) {
            $entryId !== null => Craft::$app->getElements()->getElementById($entryId, Entry::class),
            $uri !== null => Entry::find()->uri($uri)->one(),
            default => Entry::find()->slug($slug)->one(),
        };

        $description = match (true) {
            $entryId !== null => "ID {$entryId}",
            $uri !== null => "URI {$uri}",
            default => "slug {$slug}",
        };

        throw_unless($entry instanceof Entry, \InvalidArgumentException::class, "Entry with {$description} not found");

        return $entry->toArray();
    }
}
