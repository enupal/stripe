<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m200802_000000_add_index_to_order_number migration.
 */
class m200802_000000_add_index_to_order_number extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_orders}}',
                'number',
                false, true
            ),
            '{{%enupalstripe_orders}}',
            'number',
            false
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200802_000000_add_index_to_order_number cannot be reverted.\n";

        return false;
    }
}
