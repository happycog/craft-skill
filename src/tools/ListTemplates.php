<?php

namespace happycog\craftmcp\tools;

use happycog\craftmcp\services\TemplateService;

class ListTemplates
{
    public function __construct(
        protected TemplateService $templates,
    ) {
    }

    /**
     * List all site templates by filename.
     *
     * Returns template file paths relative to Craft's configured site templates directory.
     * Nested templates are returned using forward slashes.
     *
     * @return array<int, string>
     */
    public function __invoke(): array
    {
        return $this->templates->listTemplates();
    }
}
