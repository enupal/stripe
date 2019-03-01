<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft;
use enupal\stripe\services\PaymentForms;
use craft\fields\PlainText;
use craft\base\Field;
use enupal\stripe\Stripe as StripePlugin;

/**
 * m180621_000000_add_handle_to_basic_fields migration.
 */
class m180621_000000_add_handle_to_basic_fields extends Migration
{
    /**
     * @inheritdoc
     * @throws \Throwable
     */
    public function safeUp()
    {
        $fieldsService = Craft::$app->getFields();

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = StripePlugin::$app->settings->getFieldContext();
        /**
         * @var craft\fields\Matrix
         */
        $matrixBasicField = Craft::$app->fields->getFieldByHandle(PaymentForms::BASIC_FORM_FIELDS_HANDLE);

        $blockTypes = $matrixBasicField->getBlockTypes();

        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        foreach ($blockTypes as $blockType) {
            if ($blockType->handle != 'hidden'){
                /** @var Field[] $blockTypeFields */
                $blockTypeFields = $blockType->getFields();

                $blockTypeFields[] = Craft::$app->getFields()->createField([
                    'type' => PlainText::class,
                    'name' => 'Handle',
                    'handle' => 'fieldHandle',
                    'instructions' => 'How you’ll refer to this field in the templates.',
                    'required' => 1,
                    'settings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                    'translationMethod' => Field::TRANSLATION_METHOD_SITE
                ]);

                $blockType->setFields($blockTypeFields);
            }
        }

        $matrixBasicField->setBlockTypes($blockTypes);

        $fieldsService->saveField($matrixBasicField);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180621_000000_add_handle_to_basic_fields cannot be reverted.\n";

        return false;
    }
}
