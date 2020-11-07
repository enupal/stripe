<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m201107_000000_add_coupons migration.
 */
class m201107_000000_add_coupons extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        if (!$this->db->columnExists($formsTable, 'checkoutAllowPromotionCodes')) {
            $this->addColumn($formsTable, 'checkoutAllowPromotionCodes', $this->boolean()->defaultValue(false)->after('checkoutButtonText'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201107_000000_add_coupons cannot be reverted.\n";

        return false;
    }
}
