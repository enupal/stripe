<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m181113_000000_subscriptions_columns migration.
 */
class m181113_000000_subscriptions_columns extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\base\NotSupportedException
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_orders}}";

        if (!$this->db->columnExists($table, 'subscriptionStatus')) {
            $this->addColumn($table, 'subscriptionStatus', $this->string()->after('message'));
        }

        if (!$this->db->columnExists($table, 'refunded')) {
            $this->addColumn($table, 'refunded', $this->boolean()->defaultValue(false)->after('message'));
        }

        if (!$this->db->columnExists($table, 'dateRefunded')) {
            $this->addColumn($table, 'dateRefunded', $this->dateTime()->after('message'));
        }

        if (!$this->db->columnExists($table, 'isSubscription')) {
            $this->addColumn($table, 'isSubscription', $this->boolean()->defaultValue(false)->after('message'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181113_000000_subscriptions_columns cannot be reverted.\n";

        return false;
    }
}
