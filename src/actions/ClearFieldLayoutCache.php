<?php

namespace happycog\craftmcp\actions;

use craft\services\Fields;

class ClearFieldLayoutCache
{
    public function __construct(
        protected Fields $fieldsService,
    ) {
    }

    public function __invoke(): void
    {
        (function (): void {
            $this->_layouts = null;
        })->call($this->fieldsService);
    }
}
