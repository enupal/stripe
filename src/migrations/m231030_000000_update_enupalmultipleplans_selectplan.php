<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;
use Craft;

/**
 * m231030_000000_update_enupalmultipleplans_selectplan migration.
 */
class m231030_000000_update_enupalmultipleplans_selectplan extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $matrixTable = '{{%matrixcontent_enupalmultipleplans}}';

        $field = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(["name" => 'Select Plan', 'handle' => 'selectPlan', 'type' => 'craft\fields\Dropdown'])
            ->andWhere(['like', 'context', 'matrixBlockType:%', false])
            ->one();

        if (empty($field)) {
            Craft::error('Unable to find Stripe Payment field selectPlan', __METHOD__);
            return null;
        }

        $column = 'field_subscriptionPlan_selectPlan_'.$field['columnSuffix'];

        if ($this->db->columnExists($matrixTable, $column)) {
            $this->alterColumn($matrixTable, $column, $this->string(255));
        } else {
            Craft::error('Unable to find Stripe Payment select plan column: '.$column, __METHOD__);
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
