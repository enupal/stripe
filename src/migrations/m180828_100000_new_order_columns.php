<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m180828_100000_new_order_columns migration.
 */
class m180828_100000_new_order_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_orders}}";

        if (!$this->db->columnExists($table, 'userId')) {
            $this->addColumn($table, 'userId', $this->tinyInteger()->after('formId'));
        }

        if (!$this->db->columnExists($table, 'isCompleted')) {
            $this->addColumn($table, 'isCompleted', $this->tinyInteger()->after('email'));
        }

        $users = (new Query())
            ->select(['id', 'email'])
            ->from(["{{%users}}"])
            ->all();

        foreach ($users as $user) {
            $orders = (new Query())
                ->select(['id', 'email'])
                ->from([$table])
                ->where(['email' => $user['email']])
                ->all();

            foreach ($orders as $order) {
                $this->update($table, [
                    'userId' => $user['id']
                ], [
                    'id' => $order['id']
                ], [], false);
            }
        }

        $orders = (new Query())
            ->select(['id', 'email', 'orderStatusId'])
            ->from([$table])
            ->all();

        foreach ($orders as $order) {
            if ($order['orderStatusId'] != 2){
                $this->update($table, [
                    'isCompleted' => true
                ], [
                    'id' => $order['id']
                ], [], false);
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180828_100000_new_order_columns cannot be reverted.\n";

        return false;
    }
}
