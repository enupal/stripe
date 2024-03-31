<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;
use Craft;
use craft\db\Table;
use craft\fields\Matrix;
use enupal\stripe\Stripe as StripePlugin;

/**
 * m240331_000000_entrify_old_matrix_block_type_fields migration.
 */
class m240331_000000_entrify_old_matrix_block_type_fields extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $currentFieldContext = Craft::$app->getFields()->fieldContext;

        $stripeFields = (new Query())
            ->select(['id', 'type'])
            ->from(["{{%fields}}"])
            ->where(['like', 'context', 'enupalStripe:'])
            ->all();

        Craft::$app->getFields()->fieldContext = StripePlugin::$app->settings->getFieldContext();

        if ($stripeFields) {
            foreach ($stripeFields as $stripeField) {
                if ($stripeField['type'] == Matrix::class) {
                    /** @var Matrix $matrixField */
                    $matrixField = Craft::$app->fields->getFieldById($stripeField['id']);
                    foreach ($matrixField->getEntryTypes() as $entryType) {
                        $fields = $entryType->getFieldLayout()->getCustomFields();
                        foreach ($fields as $field) {
                            $this->update(Table::FIELDS, [
                                'context' => StripePlugin::$app->settings->getFieldContext(),
                            ], [
                                'uid' => $field->uid,
                            ], updateTimestamp: false);
                        }
                    }
                }
            }
        }

        // Give back the current field context
        Craft::$app->getFields()->fieldContext = $currentFieldContext;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m240331_000000_entrify_old_matrix_block_type_fields cannot be reverted.\n";

        return false;
    }
}
