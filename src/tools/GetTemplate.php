<?php

namespace happycog\craftmcp\tools;

use happycog\craftmcp\services\TemplateService;

class GetTemplate
{
    public function __construct(
        protected TemplateService $templates,
    ) {
    }

    /**
     * Get the contents of a site template by filename.
     *
     * Pass a template path relative to Craft's configured site templates directory, such as
     * `_partials/card.twig` or `index.twig`.
     *
     * @return array{filename: string, contents: string}
     */
    public function __invoke(
        string $filename,
    ): array {
        return [
            'filename' => $filename,
            'contents' => $this->templates->getTemplateContents($filename),
        ];
    }
}
