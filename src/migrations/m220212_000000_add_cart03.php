<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m220212_000000_add_cart03 migration.
 */
class m220212_000000_add_cart03 extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $ordersTable = '{{%enupalstripe_orders}}';

        if (!$this->db->columnExists($ordersTable, 'cartId')) {
            $this->addColumn($ordersTable, 'cartId', $this->integer()->after('id'));

            $this->addForeignKey(
                $this->db->getForeignKeyName(
                    $ordersTable, 'cartId'
                ),
                $ordersTable, 'cartId',
                '{{%enupalstripe_carts}}', 'id', 'CASCADE', null
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211127_000000_add_cart02 cannot be reverted.\n";

        return false;
    }
}
