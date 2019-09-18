<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;
use enupal\stripe\enums\AmountType;
use enupal\stripe\enums\SubmitTypes;

/**
 * m190918_000000_add_submit_type_to_donate migration.
 */
class m190918_000000_add_submit_type_to_donate extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $formsTable = '{{%enupalstripe_forms}}';

        $forms = (new Query())
            ->select(['*'])
            ->from([$formsTable])
            ->all();

        foreach ($forms as $form) {
            if ($form['amountType'] == AmountType::ONE_TIME_CUSTOM_AMOUNT){
                $this->update($formsTable, [
                    'checkoutSubmitType' => SubmitTypes::DONATE
                ], [
                    'id' => $form['id']
                ], [], false);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190918_000000_add_submit_type_to_donate cannot be reverted.\n";

        return false;
    }
}
