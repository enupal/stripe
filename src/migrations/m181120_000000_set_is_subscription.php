<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m181120_000000_set_is_subscription migration.
 */
class m181120_000000_set_is_subscription extends Migration
{
    /**
     * @return bool
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_orders}}";

        // Check is order is subscription
        $orders = (new Query())
            ->select(['id', 'stripeTransactionId'])
            ->from([$table])
            ->all();

        foreach ($orders as $order) {
            if ($this->isSubscription($order['stripeTransactionId'])){
                $this->update($table, [
                    'isSubscription' => true
                ], [
                    'id' => $order['id']
                ], [], false);
            }
        }

        return true;
    }

    /**
     * @param $stripeTransactionId
     * @return bool
     */
    private function isSubscription($stripeTransactionId)
    {
        $transactionId = substr($stripeTransactionId, 0, 3);

        if ($transactionId != 'sub'){
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181120_000000_set_is_subscription cannot be reverted.\n";

        return false;
    }
}
