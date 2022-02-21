<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m220212_000000_add_cart05 migration.
 */
class m220212_000000_add_cart05 extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $ordersTable = '{{%enupalstripe_orders}}';

        if (!$this->db->columnExists($ordersTable, 'cartShippingRateId')) {
            $this->addColumn($ordersTable, 'cartShippingRateId', $this->string()->after('cartPaymentMethod'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220212_000000_add_cart05 cannot be reverted.\n";

        return false;
    }
}
