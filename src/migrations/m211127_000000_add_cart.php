<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m211127_000000_add_cart migration.
 */
class m211127_000000_add_cart extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->createTable('{{%enupalstripe_products}}', [
            'id' => $this->primaryKey(),
            'stripeId' => $this->string()->notNull(),
            'stripeObject' => $this->longText(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createTable('{{%enupalstripe_prices}}', [
            'id' => $this->primaryKey(),
            'productId' => $this->integer()->notNull(),
            'stripeId' => $this->string()->notNull(),
            'stripeObject' => $this->longText(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_prices}}',
                'productId',
                false, true
            ),
            '{{%enupalstripe_prices}}',
            'productId',
            false
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_products}}',
                'stripeId',
                false, true
            ),
            '{{%enupalstripe_products}}',
            'stripeId',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_prices}}',
                'stripeId',
                false, true
            ),
            '{{%enupalstripe_prices}}',
            'stripeId',
            true
        );

        // FK

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_prices}}', 'productId'
            ),
            '{{%enupalstripe_prices}}', 'productId',
            '{{%enupalstripe_products}}', 'id', 'CASCADE', null
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211127_000000_add_cart cannot be reverted.\n";

        return false;
    }
}
