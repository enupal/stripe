<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m181124_000000_add_messages_table migration.
 */
class m181124_000000_add_messages_table extends Migration
{
    /**
     * @return bool
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_messages}}";

        if (!$this->db->tableExists($table)){
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'orderId' => $this->integer()->notNull(),
                'message' => $this->text(),
                'details' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(
                $this->db->getIndexName(
                    $table,
                    'orderId',
                    false, true
                ),
                $table,
                'orderId',
                false
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName(
                    $table, 'orderId'
                ),
                $table, 'orderId',
                '{{%enupalstripe_orders}}', 'id', 'CASCADE', null
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181124_000000_add_messages_table cannot be reverted.\n";

        return false;
    }
}
