<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m190626_000000_sca_support migration.
 */
class m190626_000000_sca_support extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'checkoutCancelUrl')) {
            $this->addColumn($formsTable, 'checkoutCancelUrl', $this->string()->after('enableCheckout'));
        }

        if (!$this->db->columnExists($formsTable, 'checkoutSuccessUrl')) {
            $this->addColumn($formsTable, 'checkoutSuccessUrl', $this->string()->after('enableCheckout'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190626_000000_sca_support cannot be reverted.\n";

        return false;
    }
}
