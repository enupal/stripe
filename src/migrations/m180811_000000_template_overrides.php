<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m180811_000000_template_overrides migration.
 */
class m180811_000000_template_overrides extends Migration
{
    /**
     * @inheritdoc
     * @throws \yii\base\NotSupportedException
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $table = "{{%enupalstripe_forms}}";

        if (!$this->db->columnExists($table, 'enableTemplateOverrides')) {
            $this->addColumn($table, 'enableTemplateOverrides', $this->tinyInteger()->after('checkoutButtonText'));
        }

        if (!$this->db->columnExists($table, 'templateOverridesFolder')) {
            $this->addColumn($table, 'templateOverridesFolder', $this->string()->after('checkoutButtonText'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180811_000000_template_overrides cannot be reverted.\n";

        return false;
    }
}
