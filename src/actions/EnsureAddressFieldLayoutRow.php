<?php

namespace happycog\craftmcp\actions;

use Craft;
use craft\db\Table;
use craft\elements\Address;
use craft\models\FieldLayout;

class EnsureAddressFieldLayoutRow
{
    public function __construct(
        protected ClearFieldLayoutCache $clearFieldLayoutCache,
    ) {
    }

    public function __invoke(FieldLayout $fieldLayout): FieldLayout
    {
        $uid = $fieldLayout->uid;
        throw_unless(is_string($uid) && $uid !== '', 'Address field layout UID is missing.');

        $db = Craft::$app->getDb();
        $row = (new \yii\db\Query())
            ->from(Table::FIELDLAYOUTS)
            ->where(['uid' => $uid])
            ->one();

        if (is_array($row)) {
            $db->createCommand()->update(Table::FIELDLAYOUTS, [
                'type' => Address::class,
                'config' => $fieldLayout->getConfig(),
            ], ['uid' => $uid])->execute();
        }

        $existingId = $row['id'] ?? null;

        if (!is_int($existingId) && !ctype_digit((string) $existingId)) {
            $db->createCommand()->insert(Table::FIELDLAYOUTS, [
                'type' => Address::class,
                'uid' => $uid,
                'config' => $fieldLayout->getConfig(),
            ])->execute();

            $existingId = (new \yii\db\Query())
                ->from(Table::FIELDLAYOUTS)
                ->where(['uid' => $uid])
                ->scalar();
        }

        throw_unless(is_int($existingId) || ctype_digit((string) $existingId), 'Failed to ensure address field layout row exists.');

        $fieldLayout->id = (int) $existingId;
        ($this->clearFieldLayoutCache)();

        return $fieldLayout;
    }
}
