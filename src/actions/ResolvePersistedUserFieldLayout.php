<?php

namespace happycog\craftmcp\actions;

use Craft;
use craft\db\Table;
use craft\elements\User;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class ResolvePersistedUserFieldLayout
{
    public function __construct(
        protected ClearFieldLayoutCache $clearFieldLayoutCache,
    ) {
    }

    public function __invoke(): FieldLayout
    {
        ($this->clearFieldLayoutCache)();

        $layoutConfig = (new \yii\db\Query())
            ->from(Table::FIELDLAYOUTS)
            ->where(['type' => User::class])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (is_array($layoutConfig)) {
            $rawConfig = $layoutConfig['config'] ?? null;
            unset($layoutConfig['config'], $layoutConfig['dateCreated'], $layoutConfig['dateUpdated'], $layoutConfig['dateDeleted']);
            /** @var FieldLayout $layout */
            $layout = Craft::$app->getFields()->createLayout($layoutConfig);

            if (is_string($rawConfig) && $rawConfig !== '') {
                $decodedConfig = \craft\helpers\Json::decode($rawConfig);
                if (is_array($decodedConfig)) {
                    $tabs = [];
                    foreach (($decodedConfig['tabs'] ?? []) as $tabConfig) {
                        if (!is_array($tabConfig)) {
                            continue;
                        }

                        $tabs[] = new FieldLayoutTab([
                            'layout' => $layout,
                            'name' => $tabConfig['name'] ?? 'Content',
                            'uid' => $tabConfig['uid'] ?? null,
                            'elements' => $tabConfig['elements'] ?? [],
                        ]);
                    }

                    if ($tabs !== []) {
                        $layout->setTabs($tabs);
                    }
                }
            }

            return $layout;
        }

        $layout = Craft::$app->getFields()->getLayoutByType(User::class);
        throw_unless($layout instanceof FieldLayout, 'User field layout could not be resolved.');

        return $layout;
    }
}
