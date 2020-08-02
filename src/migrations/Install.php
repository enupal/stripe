<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\migrations;

use craft\db\Migration;
use enupal\stripe\enums\SubmitTypes;
use Craft;

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
        $this->dropTableIfExists('{{%enupalstripe_messages}}');
        $this->dropTableIfExists('{{%enupalstripe_orders}}');
        $this->dropTableIfExists('{{%enupalstripe_forms}}');
        $this->dropTableIfExists('{{%enupalstripe_customers}}');
        $this->dropTableIfExists('{{%enupalstripe_orderstatuses}}');
        $this->dropTableIfExists('{{%enupalstripe_addresses}}');
        $this->dropTableIfExists('{{%enupalstripe_countries}}');
        $this->dropTableIfExists('{{%enupalstripe_subscriptiongrants}}');
        $this->dropTableIfExists('{{%enupalstripe_commissions}}');
        $this->dropTableIfExists('{{%enupalstripe_connect}}');
        $this->dropTableIfExists('{{%enupalstripe_vendors}}');

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
            'enableCheckout' => $this->boolean()->defaultValue(true),
            'paymentType' => $this->string(),
            'checkoutPaymentType' => $this->string(),
            'checkoutCancelUrl' => $this->string(),
            'checkoutSubmitType' => $this->string()->defaultValue(SubmitTypes::PAY),
            'checkoutSuccessUrl' => $this->string(),
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
            'hasUnlimitedStock' => $this->boolean()->defaultValue(true),
            'customerQuantity' => $this->boolean(),
            'soldOutMessage' => $this->string(),
            // Shipping
            'verifyZip' => $this->boolean(),
            'enableBillingAddress' => $this->boolean(),
            'enableShippingAddress' => $this->boolean(),
            'shippingAmount' => $this->decimal(14, 4),
            // Weight
            'itemWeight' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            'itemWeightUnit' => $this->string(),
            // Customer
            'showItemName' => $this->boolean()->defaultValue(false),
            'showItemPrice' => $this->boolean()->defaultValue(false),
            'showItemCurrency' => $this->boolean()->defaultValue(false),
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
            'testMode' => $this->boolean()->defaultValue(false),
            'billingAddressId' => $this->integer(),
            'shippingAddressId' => $this->integer(),
            'paymentType' => $this->integer(),
            'number' => $this->string(),
            'currency' => $this->string(),
            'totalPrice' => $this->decimal(14, 4)->defaultValue(0),
            'shipping' => $this->decimal(14, 4)->defaultValue(0),
            'tax' => $this->decimal(14, 4)->defaultValue(0),
            'couponCode' => $this->string(),
            'couponName' => $this->string(),
            'couponAmount' => $this->decimal(14, 4),
            'couponSnapshot' => $this->longText(),
            'quantity' => $this->integer(),
            'dateOrdered' => $this->dateTime(),
            'orderStatusId' => $this->integer(),
            'stripeTransactionId' => $this->string(),
            'transactionInfo' => $this->text(),
            'email' => $this->string(),
            'isCompleted' => $this->boolean(),
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

        $this->createTable('{{%enupalstripe_addresses}}', [
            'id' => $this->primaryKey(),
            'countryId' => $this->integer(),
            'attention' => $this->string(),
            'title' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'address1' => $this->string(),
            'address2' => $this->string(),
            'city' => $this->string(),
            'zipCode' => $this->string(),
            'phone' => $this->string(),
            'alternativePhone' => $this->string(),
            'businessName' => $this->string(),
            'businessTaxId' => $this->string(),
            'businessId' => $this->string(),
            'stateName' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalstripe_countries}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'iso' => $this->string(2)->notNull(),
            'isStateRequired' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
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

        $this->createTable("{{%enupalstripe_subscriptiongrants}}", [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'planId' => $this->string()->notNull(),
            'planName' => $this->string(),
            'userGroupId' => $this->integer(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'removeWhenCanceled' => $this->boolean(),
            'enabled' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalstripe_connect}}', [
            'id' => $this->primaryKey(),
            'vendorId' => $this->integer(),
            'products' => $this->string(),
            // Stripe Payment Form or Craft Commerce Product
            'productType' => $this->string()->notNull(),
            'allProducts' => $this->boolean()->defaultValue(false),
            'rate' => $this->decimal(14, 2)->defaultValue(0),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%enupalstripe_vendors}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'stripeId' => $this->string(),
            'paymentType' => $this->string(),
            'skipAdminReview' => $this->boolean()->defaultValue(false),
            'vendorRate' => $this->decimal(14, 2)->defaultValue(0),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);
        // For each connect that match it will be a unique commission
        $this->createTable('{{%enupalstripe_commissions}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'productId' => $this->integer()->notNull(),
            'connectId' => $this->integer()->notNull(),
            'stripeId' => $this->string(),
            'number' => $this->string(),
            // Order class namespace
            'orderType' => $this->string()->notNull(),
            'commissionStatus' => $this->string(),
            'totalPrice' => $this->decimal(14, 4)->defaultValue(0),
            'currency' => $this->string(),
            'testMode' => $this->boolean()->defaultValue(false),
            'datePaid' => $this->dateTime(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
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
                '{{%enupalstripe_orders}}',
                'number',
                false, true
            ),
            '{{%enupalstripe_orders}}',
            'number',
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

        $this->createIndex(null, '{{%enupalstripe_addresses}}', 'countryId', false);
        $this->createIndex(null, '{{%enupalstripe_countries}}', 'name', true);
        $this->createIndex(null, '{{%enupalstripe_countries}}', 'iso', true);
        $this->createIndex(null, '{{%enupalstripe_orders}}', 'billingAddressId', false);
        $this->createIndex(null, '{{%enupalstripe_orders}}', 'shippingAddressId', false);
        $this->createIndex(null, "{{%enupalstripe_subscriptiongrants}}", 'userGroupId', false);
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_connect}}',
                'vendorId',
                false, true
            ),
            '{{%enupalstripe_connect}}',
            'vendorId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_vendors}}',
                'userId',
                false, true
            ),
            '{{%enupalstripe_vendors}}',
            'userId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_commissions}}',
                'orderId',
                false, true
            ),
            '{{%enupalstripe_commissions}}',
            'orderId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_commissions}}',
                'productId',
                false, true
            ),
            '{{%enupalstripe_commissions}}',
            'productId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_commissions}}',
                'connectId',
                false, true
            ),
            '{{%enupalstripe_commissions}}',
            'connectId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_commissions}}',
                'number',
                false, true
            ),
            '{{%enupalstripe_commissions}}',
            'number',
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

        $this->addForeignKey(null, '{{%enupalstripe_addresses}}', ['countryId'], '{{%enupalstripe_countries}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%enupalstripe_orders}}', ['billingAddressId'], '{{%enupalstripe_addresses}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%enupalstripe_orders}}', ['shippingAddressId'], '{{%enupalstripe_addresses}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, "{{%enupalstripe_subscriptiongrants}}", ['userGroupId'], "{{%usergroups}}", ['id'], 'CASCADE');
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_connect}}', 'id'
            ),
            '{{%enupalstripe_connect}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_vendors}}', 'id'
            ),
            '{{%enupalstripe_vendors}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_commissions}}', 'id'
            ),
            '{{%enupalstripe_commissions}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_connect}}', 'vendorId'
            ),
            '{{%enupalstripe_connect}}', 'vendorId',
            '{{%enupalstripe_vendors}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_commissions}}', 'orderId'
            ),
            '{{%enupalstripe_commissions}}', 'orderId',
            '{{%elements}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_commissions}}', 'productId'
            ),
            '{{%enupalstripe_commissions}}', 'productId',
            '{{%elements}}', 'id', 'CASCADE', null
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_commissions}}', 'connectId'
            ),
            '{{%enupalstripe_commissions}}', 'connectId',
            '{{%enupalstripe_connect}}', 'id', 'CASCADE', null
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @throws \yii\db\Exception
     */
    protected function insertDefaultData()
    {
        $this->_defaultCountries();

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

    /**
     * Insert default countries data.
     */
    private function _defaultCountries()
    {
        $migration = new m190126_000000_insert_countries();

        ob_start();
        $migration->safeUp();
        ob_end_clean();
    }
}