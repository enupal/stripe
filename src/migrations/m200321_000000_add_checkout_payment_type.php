<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m200321_000000_add_checkout_payment_type migration.
 */
class m200321_000000_add_checkout_payment_type extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'checkoutPaymentType')) {
            $this->addColumn($formsTable, 'checkoutPaymentType', $this->string()->after('paymentType'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200321_000000_add_checkout_payment_type cannot be reverted.\n";

        return false;
    }
}
