<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m211127_000000_add_cart02 migration.
 */
class m211127_000000_add_cart02 extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->createTable('{{%enupalstripe_carts}}', [
            'id' => $this->primaryKey(),
            'number' => $this->string()->notNull(),
            'stripeId' => $this->string(),
            'items' => $this->longText(),
            'totalPrice' => $this->decimal(14, 4)->defaultValue(0),
            'itemCount' => $this->integer(),
            'currency' => $this->string(),
            'userEmail' => $this->string(),
            'userId' => $this->integer(),
            'status' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ]);

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_carts}}',
                'stripeId',
                false, true
            ),
            '{{%enupalstripe_carts}}',
            'stripeId',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_carts}}',
                'number',
                false, true
            ),
            '{{%enupalstripe_carts}}',
            'number',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%enupalstripe_carts}}',
                'userId',
                false, true
            ),
            '{{%enupalstripe_carts}}',
            'userId',
            false
        );

        // FK

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalstripe_carts}}', 'productId'
            ),
            '{{%enupalstripe_carts}}', 'userId',
            '{{%users}}', 'id', 'CASCADE', null
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211127_000000_add_cart02 cannot be reverted.\n";

        return false;
    }
}
