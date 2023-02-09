<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m230208_000000_add_adjustable_quantity migration.
 */
class m230208_000000_add_adjustable_quantity extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'adjustableQuantity')) {
            $this->addColumn($formsTable, 'adjustableQuantity', $this->boolean()->defaultValue(false)->after('automaticTax'));
        }

        if (!$this->db->columnExists($formsTable, 'adjustableQuantityMin')) {
            $this->addColumn($formsTable, 'adjustableQuantityMin', $this->integer()->after('automaticTax'));
        }

        if (!$this->db->columnExists($formsTable, 'adjustableQuantityMax')) {
            $this->addColumn($formsTable, 'adjustableQuantityMax', $this->integer()->after('automaticTax'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m230208_000000_add_adjustable_quantity cannot be reverted.\n";

        return false;
    }
}
