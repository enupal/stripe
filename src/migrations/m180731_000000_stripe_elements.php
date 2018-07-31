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
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_forms}}";

        if (!$this->db->columnExists($table, 'enableCheckout')) {
            $this->addColumn($table, 'enableCheckout', $this->tinyInteger()->after('handle')->defaultValue(1));
        }

        if (!$this->db->columnExists($table, 'paymentType')) {
            $this->addColumn($table, 'paymentType', $this->string()->after('handle'));
        }

        if (!$this->db->columnExists($table, 'postData')) {
            $this->addColumn($table, 'postData', $this->text()->after('checkoutButtonText'));
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
