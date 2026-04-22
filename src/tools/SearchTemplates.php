<?php

namespace happycog\craftmcp\tools;

use happycog\craftmcp\services\TemplateService;

class SearchTemplates
{
    public function __construct(
        protected TemplateService $templates,
    ) {
    }

    /**
     * Search site templates for a plain-text needle string.
     *
     * Returns each matching line with its template filename and line number. Matching is a
     * simple case-sensitive substring search across all files in Craft's configured site
     * templates directory.
     *
     * @return array{_notes: string, results: array<int, array{filename: string, lineNumber: int, line: string}>}
     */
    public function __invoke(
        string $needle,
    ): array {
        $results = $this->templates->searchTemplates($needle);
        $count = count($results);

        return [
            '_notes' => $count === 0
                ? "No template matches found for needle \"{$needle}\"."
                : "Found {$count} template match(es) for needle \"{$needle}\".",
            'results' => $results,
        ];
    }
}
