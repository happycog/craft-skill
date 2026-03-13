<?php

namespace happycog\craftmcp\actions;

use craft\models\FieldLayout;

class GetFreshAddressFieldLayout
{
    public function __construct(
        protected ResolvePersistedAddressFieldLayout $resolvePersistedAddressFieldLayout,
    ) {
    }

    public function __invoke(): FieldLayout
    {
        return ($this->resolvePersistedAddressFieldLayout)();
    }
}
