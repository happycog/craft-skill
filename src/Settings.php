<?php

namespace happycog\craftmcp;

use craft\base\Model;

class Settings extends Model
{
    public string $mcpPath = 'mcp';

    /**
     * @return array<string, mixed>
     */
    protected function defineRules(): array
    {
        return [
            [['mcpPath'], 'required'],
            [['mcpPath'], 'string'],
        ];
    }
}
