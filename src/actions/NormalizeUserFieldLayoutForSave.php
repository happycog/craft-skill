<?php

namespace happycog\craftmcp\actions;

use craft\models\FieldLayout;
use happycog\craftmcp\tools\GetUserFieldLayout;

class NormalizeUserFieldLayoutForSave
{
    public function __construct(
        protected EnsureUserFieldLayoutRow $ensureUserFieldLayoutRow,
    ) {
    }

    public function __invoke(FieldLayout $fieldLayout): FieldLayout
    {
        $normalizedLayout = clone $fieldLayout;
        $normalizedLayout->setTabs($fieldLayout->getTabs());

        if ($normalizedLayout->id === GetUserFieldLayout::PLACEHOLDER_ID) {
            $normalizedLayout->id = null;
        }

        if ($normalizedLayout->id !== null && !$this->layoutExists($normalizedLayout->id)) {
            $normalizedLayout->id = null;
        }

        if ($normalizedLayout->id === null) {
            $normalizedLayout = ($this->ensureUserFieldLayoutRow)($normalizedLayout);
        }

        return $normalizedLayout;
    }

    private function layoutExists(int $fieldLayoutId): bool
    {
        return (new \yii\db\Query())
            ->from('{{%fieldlayouts}}')
            ->where(['id' => $fieldLayoutId])
            ->exists();
    }
}
