<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m190228_000000_add_field_context migration.
 */
class m190228_000000_add_field_context extends Migration
{
    /**
     * @return bool
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['*'])
            ->from('{{%fields}}')
            ->where(["context" => 'enupalStripe:'])
            ->all();

        $plugin = (new Query())
            ->select(['uid'])
            ->from('{{%plugins}}')
            ->where(["handle" => 'enupal-stripe'])
            ->one();

        $context = 'enupalStripe:'.$plugin['uid'];

        foreach ($fields as $field) {
            $this->update('{{%fields}}', ['context' => $context], ['id' => $field['id']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190228_000000_add_field_context cannot be reverted.\n";

        return false;
    }
}
