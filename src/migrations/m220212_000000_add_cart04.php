<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m220212_000000_add_cart04 migration.
 */
class m220212_000000_add_cart04 extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $ordersTable = '{{%enupalstripe_orders}}';

        if (!$this->db->columnExists($ordersTable, 'cartPaymentMethod')) {
            $this->addColumn($ordersTable, 'cartPaymentMethod', $this->string()->after('isSubscription'));
        }

        if (!$this->db->columnExists($ordersTable, 'cartItems')) {
            $this->addColumn($ordersTable, 'cartItems', $this->longText()->after('isSubscription'));
        }

        if (!$this->db->columnExists($ordersTable, 'isCart')) {
            $this->addColumn($ordersTable, 'isCart', $this->boolean()->defaultValue(false)->after('isSubscription'));

            $this->createIndex(
                $this->db->getIndexName(
                    $ordersTable,
                    'isCart',
                    false, true
                ),
                '{{%enupalstripe_orders}}',
                'isCart',
                false
            );
        }

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_orders}}',
                'isSubscription',
                false, true
            ),
            '{{%enupalstripe_orders}}',
            'isSubscription',
            false
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220212_000000_add_cart04 cannot be reverted.\n";

        return false;
    }
}
