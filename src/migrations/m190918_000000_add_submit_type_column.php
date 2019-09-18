<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use enupal\stripe\enums\SubmitTypes;

/**
 * m190918_000000_add_submit_type_column migration.
 */
class m190918_000000_add_submit_type_column extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'checkoutSubmitType')) {
            $this->addColumn($formsTable, 'checkoutSubmitType', $this->string()->defaultValue(SubmitTypes::PAY)->after('enableCheckout'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190918_000000_add_submit_type_column cannot be reverted.\n";

        return false;
    }
}
