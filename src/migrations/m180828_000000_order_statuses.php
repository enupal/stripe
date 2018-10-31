<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m180828_000000_order_statuses migration.
 */
class m180828_000000_order_statuses extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_orderstatuses}}";

        $this->createTable($table, [
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
            ],
            2 => [
                'name' => 'Pending',
                'handle' => 'pending',
                'color' => 'grey',
                'sortOrder' => 3,
                'isDefault' => 0
            ]
        ];

        foreach ($defaultEntryStatuses as $entryStatus) {
            $this->db->createCommand()->insert($table, [
                'name' => $entryStatus['name'],
                'handle' => $entryStatus['handle'],
                'color' => $entryStatus['color'],
                'sortOrder' => $entryStatus['sortOrder'],
                'isDefault' => $entryStatus['isDefault']
            ])->execute();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180828_000000_order_statuses cannot be reverted.\n";

        return false;
    }
}
