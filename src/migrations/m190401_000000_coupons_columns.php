<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m190401_000000_coupons_columns migration.
 */
class m190401_000000_coupons_columns extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';
        $table = '{{%enupalstripe_orders}}';
        // delete unused columns
        if ($this->getDb()->columnExists($formsTable, 'discount')){
            $this->dropColumn($formsTable, 'discount');
        }

        if ($this->getDb()->columnExists($table, 'discount')){
            $this->dropColumn($table, 'discount');
        }

        if ($this->getDb()->columnExists($formsTable, 'discountType')){
            $this->dropColumn($formsTable, 'discountType');
        }

        // New coupon columns
        if (!$this->db->columnExists($table, 'couponCode')) {
            $this->addColumn($table, 'couponCode',  $this->string()->after('tax'));
        }

        if (!$this->db->columnExists($table, 'couponName')) {
            $this->addColumn($table, 'couponName',  $this->string()->after('tax'));
        }

        if (!$this->db->columnExists($table, 'couponAmount')) {
            $this->addColumn($table, 'couponAmount', $this->decimal(14, 4)->after('tax'));
        }

        if (!$this->db->columnExists($table, 'couponSnapshot')) {
            $this->addColumn($table, 'couponSnapshot', $this->longText()->after('tax'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190401_000000_coupons_columns cannot be reverted.\n";

        return false;
    }
}
