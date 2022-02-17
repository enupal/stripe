<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m220216_000000_add_automatic_tax migration.
 */
class m220216_000000_add_automatic_tax extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'automaticTax')) {
            $this->addColumn($formsTable, 'automaticTax', $this->boolean()->defaultValue(false)->after('enableCheckout'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220216_000000_add_automatic_tax cannot be reverted.\n";

        return false;
    }
}
