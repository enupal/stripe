<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m200308_000000_subscription_grants migration.
 */
class m200308_000000_subscription_grants extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_subscriptiongrants}}";

        $this->createTable($table, [
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

        $this->createIndex(null, $table, 'userGroupId', false);
        $this->addForeignKey(null, $table, ['userGroupId'], "{{%usergroups}}", ['id'], 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200308_000000_subscription_grants cannot be reverted.\n";

        return false;
    }
}
