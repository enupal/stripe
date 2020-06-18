<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m200617_000000_add_stripe_connect migration.
 */
class m200617_000000_add_stripe_connect extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->createTable('{{%enupalstripe_connect}}', [
            'id' => $this->primaryKey(),
            'vendorId' => $this->integer()->notNull()->unique(),
            'products' => $this->string()->notNull(),
            // Stripe Payment Form or Craft Commerce Product
            'productType' => $this->string()->notNull(),
            'allProducts' => $this->boolean(),
            'rate' => $this->decimal(14, 4)->defaultValue(0),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%enupalstripe_vendors}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'stripeId' => $this->string(),
            // On checkout - Manually
            'paymentType' => $this->string(),
            'skipAdminReview' => $this->boolean()->defaultValue(false),
            'vendorRate' => $this->decimal(14, 4)->defaultValue(0),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);
        // For each connect that match it will be a unique commission
        $this->createTable('{{%enupalstripe_commissions}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'vendorId' => $this->integer()->notNull(),
            // Order class namespace
            'orderType' => $this->string()->notNull(),
            'commissionStatus' => $this->string(),
            'totalPrice' => $this->decimal(14, 4)->defaultValue(0),
            'currency' => $this->string(),
            'datePaid' => $this->dateTime(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);


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
                'vendorId',
                false, true
            ),
            '{{%enupalstripe_commissions}}',
            'vendorId',
            false
        );

        // FK

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
                '{{%enupalstripe_commissions}}', 'vendorId'
            ),
            '{{%enupalstripe_commissions}}', 'vendorId',
            '{{%elements}}', 'id', 'CASCADE', null
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200617_000000_add_stripe_connect cannot be reverted.\n";

        return false;
    }
}
