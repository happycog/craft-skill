<?php

namespace happycog\craftmcp\actions;

use craft\models\FieldLayout;

class GetFreshUserFieldLayout
{
    public function __construct(
        protected ResolvePersistedUserFieldLayout $resolvePersistedUserFieldLayout,
    ) {
    }

    public function __invoke(): FieldLayout
    {
        return ($this->resolvePersistedUserFieldLayout)();
    }
}
