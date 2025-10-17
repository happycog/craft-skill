<?php

namespace happycog\craftmcp;

use craft\base\Model;

class Settings extends Model
{
    public string $apiPrefix = 'api';

    /**
     * @return array<string, mixed>
     */
    protected function defineRules(): array
    {
        return [
            [['apiPrefix'], 'required'],
            [['apiPrefix'], 'string'],
        ];
    }
}

