<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * Installation Migration
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%enupalstripe_orders}}');
        $this->dropTableIfExists('{{%enupalstripe_forms}}');
        $this->dropTableIfExists('{{%enupalstripe_customers}}');
        $this->dropTableIfExists('{{%enupalstripe_orderstatuses}}');

        return true;
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%enupalstripe_forms}}', [
            'id' => $this->primaryKey(),
            'companyName' => $this->string(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'enableCheckout' => $this->boolean()->defaultValue(1),
            'paymentType' => $this->string(),
            'currency' => $this->string()->defaultValue('USD'),
            'language' => $this->string()->defaultValue('en'),
            'amountType' => $this->integer(),
            'minimumAmount' => $this->decimal(14, 4)->defaultValue(0),
            'customAmountLabel' => $this->string(),
            'amount' => $this->decimal(14, 4)->defaultValue(0),
            'logoImage' => $this->string(),
            'enableRememberMe' => $this->boolean(),
            // Recurring
            'enableRecurringPayment' => $this->boolean(),
            'recurringPaymentType' => $this->string(),
            // Subscriptions
            'enableSubscriptions' => $this->boolean(),
            'subscriptionType' => $this->integer(),
            'singlePlanSetupFee' => $this->decimal(14, 4),
            'singlePlanTrialPeriod' => $this->integer(),
            'singlePlanInfo' => $this->text(),
            'enableCustomPlanAmount' => $this->boolean(),
            'customPlanMinimumAmount' => $this->decimal(14, 4)->defaultValue(0),
            'customPlanDefaultAmount' => $this->decimal(14, 4),
            'customPlanInterval' => $this->integer(),
            'customPlanFrequency' => $this->string(),

            'subscriptionStyle' => $this->string(),
            'selectPlanLabel' => $this->string(),
            // Inventory
            'quantity' => $this->integer(),
            'hasUnlimitedStock' => $this->boolean()->defaultValue(1),
            'customerQuantity' => $this->boolean(),
            'soldOutMessage' => $this->string(),
            // Discounts
            'discountType' => $this->integer()->defaultValue(0),
            'discount' => $this->decimal(14, 4),
            // Shipping
            'verifyZip' => $this->boolean(),
            'enableBillingAddress' => $this->boolean(),
            'enableShippingAddress' => $this->boolean(),
            'shippingAmount' => $this->decimal(14, 4),
            // Weight
            'itemWeight' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            'itemWeightUnit' => $this->string(),
            // Customer
            'showItemName' => $this->boolean()->defaultValue(0),
            'showItemPrice' => $this->boolean()->defaultValue(0),
            'showItemCurrency' => $this->boolean()->defaultValue(0),
            'returnUrl' => $this->string(),
            'buttonClass' => $this->string(),
            // Button
            'buttonText' => $this->string(),
            'paymentButtonProcessingText' => $this->string(),
            'checkoutButtonText' => $this->string(),
            'enableTemplateOverrides' => $this->boolean(),
            'templateOverridesFolder' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalstripe_orders}}', [
            'id' => $this->primaryKey(),
            'formId' => $this->integer(),
            'userId' => $this->integer(),
            'testMode' => $this->boolean()->defaultValue(0),
            'paymentType' => $this->integer(),
            'number' => $this->string(),
            'currency' => $this->string(),
            'totalPrice' => $this->decimal(14, 4)->defaultValue(0),
            'shipping' => $this->decimal(14, 4)->defaultValue(0),
            'tax' => $this->decimal(14, 4)->defaultValue(0),
            'discount' => $this->decimal(14, 4)->defaultValue(0),
            'quantity' => $this->integer(),
            'dateOrdered' => $this->dateTime(),
            'orderStatusId' => $this->integer(),
            'stripeTransactionId' => $this->string(),
            'transactionInfo' => $this->text(),
            'email' => $this->string(),
            'isCompleted' => $this->boolean(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'addressCity' => $this->string(),
            'addressCountry' => $this->string(),
            'addressState' => $this->string(),
            'addressCountryCode' => $this->string(),
            'addressName' => $this->string(),
            'addressStreet' => $this->string(),
            'addressZip' => $this->string(),
            'variants' => $this->text(),
            'postData' => $this->text(),
            'message' => $this->text(),
            'subscriptionStatus' => $this->string(),
            'refunded' => $this->boolean()->defaultValue(false),
            'dateRefunded' => $this->dateTime(),
            'isSubscription' => $this->boolean()->defaultValue(false),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%enupalstripe_customers}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(),
            'stripeId' => $this->string(),
            'testMode' => $this->boolean(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%enupalstripe_orderstatuses}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'color' => $this->enum('color',
                [
                    'green', 'orange', 'red', 'blue',
                    'yellow', 'pink', 'purple', 'turquoise',
                    'light', 'grey', 'black'
                ])
                ->notNull()->defaultValue('blue'),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'isDefault' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalstripe_messages}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'message' => $this->text(),
            'details' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_orders}}',
                'formId',
                false, true
            ),
            '{{%enupalstripe_orders}}',
            'formId',
            false
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_messages}}',
                'orderId',
                false, true
            ),
            '{{%enupalstripe_messages}}',
            'orderId',
            false
        );
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_forms}}', 'id'
            ),
            '{{%enupalstripe_forms}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_orders}}', 'id'
            ),
            '{{%enupalstripe_orders}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_orders}}', 'formId'
            ),
            '{{%enupalstripe_orders}}', 'formId',
            '{{%enupalstripe_forms}}', 'id', 'CASCADE', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_messages}}', 'orderId'
            ),
            '{{%enupalstripe_messages}}', 'orderId',
            '{{%enupalstripe_orders}}', 'id', 'CASCADE', null
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @throws \yii\db\Exception
     */
    protected function insertDefaultData()
    {
        // populate default Order Statuses
        $defaultEntryStatuses = [
            0 => [
                'name' => 'New',
                'handle' => 'new',
                'color' => 'green',
                'sortOrder' => 1,
                'isDefault' => 1
            ],
            1 => [
                'name' => 'Processed',
                'handle' => 'processed',
                'color' => 'blue',
                'sortOrder' => 2,
                'isDefault' => 0
            ]
        ];

        foreach ($defaultEntryStatuses as $entryStatus) {
            $this->db->createCommand()->insert('{{%enupalstripe_orderstatuses}}', [
                'name' => $entryStatus['name'],
                'handle' => $entryStatus['handle'],
                'color' => $entryStatus['color'],
                'sortOrder' => $entryStatus['sortOrder'],
                'isDefault' => $entryStatus['isDefault']
            ])->execute();
        }
    }
}