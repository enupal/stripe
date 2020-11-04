<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m201104_000000_add_tax_support migration.
 */
class m201104_000000_add_tax_support extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'tax')) {
            $this->addColumn($formsTable, 'tax', $this->text()->after('templateOverridesFolder'));
        }

        if (!$this->db->columnExists($formsTable, 'useDynamicTaxRate')) {
            $this->addColumn($formsTable, 'useDynamicTaxRate', $this->boolean()->defaultValue(false)->after('templateOverridesFolder'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201104_000000_add_tax_support cannot be reverted.\n";

        return false;
    }
}
