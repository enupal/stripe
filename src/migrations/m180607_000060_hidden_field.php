<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft;
use enupal\stripe\services\PaymentForms;
use craft\fields\PlainText;
use craft\base\Field;
use enupal\stripe\Stripe as StripePlugin;

/**
 * m180607_000060_hidden_field migration.
 */
class m180607_000060_hidden_field extends Migration
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
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        $blockTypes = $matrixBasicField->getBlockTypes();

        $blockTypes['new1'] = [
            'name' => 'Hidden',
            'handle' => 'hidden',
            'fields' => [
                'new1' => [
                    'type' => PlainText::class,
                    'name' => 'Label',
                    'handle' => 'label',
                    'instructions' => 'This field will not visible in the form, just in the source code',
                    'required' => 1,
                    'typesettings' => '{"placeholder":"","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                    'translationMethod' => Field::TRANSLATION_METHOD_SITE,
                ],
                'new2' => [
                    'type' => PlainText::class,
                    'name' => 'Hidden Value',
                    'handle' => 'hiddenValue',
                    'instructions' => 'You can use twig code',
                    'required' => 1,
                    'typesettings' => '{"placeholder":"{{ craft.request.path }}","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                    'translationMethod' => Field::TRANSLATION_METHOD_SITE
                ]
            ]
        ];

        $matrixBasicField->setBlockTypes($blockTypes);

        $fieldsService->saveField($matrixBasicField);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180607_000060_hidden_field cannot be reverted.\n";

        return false;
    }
}
