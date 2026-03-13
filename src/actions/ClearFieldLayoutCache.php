<?php

namespace happycog\craftmcp\actions;

use craft\services\Entries;
use craft\services\Fields;

class ClearFieldLayoutCache
{
    public function __construct(
        protected Entries $entriesService,
        protected Fields $fieldsService,
    ) {
    }

    public function __invoke(): void
    {
        (function (): void {
            $this->_layouts = null;
        })->call($this->fieldsService);

        (function (): void {
            $this->_sections = null;
            $this->_entryTypes = null;
        })->call($this->entriesService);
    }
}
