<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m180731_000000_stripe_elements migration.
 */
class m180731_000000_stripe_elements extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\base\NotSupportedException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_forms}}";
        $orderTable = "{{%enupalstripe_orders}}";

        if (!$this->db->columnExists($table, 'enableCheckout')) {
            $this->addColumn($table, 'enableCheckout', $this->tinyInteger()->after('handle')->defaultValue(1));
        }

        if (!$this->db->columnExists($table, 'paymentType')) {
            $this->addColumn($table, 'paymentType', $this->string()->after('handle'));
        }

        if (!$this->db->columnExists($orderTable, 'paymentType')) {
            $this->addColumn($orderTable, 'paymentType', $this->integer()->after('testMode'));
        }

        if (!$this->db->columnExists("{{%enupalstripe_orders}}", 'postData')) {
            $this->addColumn("{{%enupalstripe_orders}}", 'postData', $this->text()->after('variants'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180731_000000_stripe_elements cannot be reverted.\n";

        return false;
    }
}
