<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\migrations;

use craft\db\Migration;
use enupal\stripe\enums\PaypalSize;

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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%enupalstripe_orders}}');
        $this->dropTableIfExists('{{%enupalstripe_buttons}}');

        return true;
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%enupalstripe_buttons}}', [
            'id' => $this->primaryKey(),
            'companyName' => $this->string(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'currency' => $this->string()->defaultValue('USD'),
            'language' => $this->string()->defaultValue('en'),
            'amountType' => $this->integer(),
            'minimumAmount' => $this->decimal(14, 4)->defaultValue(0),
            'customAmountLabel' => $this->string(),
            'amount' => $this->decimal(14, 4)->defaultValue(0),
            'logoImage' => $this->string(),
            'enableRememberMe' => $this->boolean(),
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
            // Button
            'buttonText' => $this->string(),
            'paymentButtonProcessingText' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalstripe_orders}}', [
            'id' => $this->primaryKey(),
            'buttonId' => $this->integer(),
            'testMode' => $this->boolean()->defaultValue(0),
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
                'buttonId',
                false, true
            ),
            '{{%enupalstripe_orders}}',
            'buttonId',
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
                '{{%enupalstripe_buttons}}', 'id'
            ),
            '{{%enupalstripe_buttons}}', 'id',
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
                '{{%enupalstripe_orders}}', 'buttonId'
            ),
            '{{%enupalstripe_orders}}', 'buttonId',
            '{{%enupalstripe_buttons}}', 'id', 'CASCADE', null
        );
    }
}