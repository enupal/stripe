<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;

/**
 * m190125_000000_address_tables migration.
 */
class m190125_000000_address_tables extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $addressTable = '{{%enupalstripe_addresses}}';
        if (!$this->db->tableExists($addressTable)){
            $this->createTable($addressTable, [
                'id' => $this->primaryKey(),
                'countryId' => $this->integer(),
                'attention' => $this->string(),
                'title' => $this->string(),
                'firstName' => $this->string(),
                'lastName' => $this->string(),
                'address1' => $this->string(),
                'address2' => $this->string(),
                'city' => $this->string(),
                'zipCode' => $this->string(),
                'phone' => $this->string(),
                'alternativePhone' => $this->string(),
                'businessName' => $this->string(),
                'businessTaxId' => $this->string(),
                'businessId' => $this->string(),
                'stateName' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
            $this->createIndex(null, '{{%enupalstripe_addresses}}', 'countryId', false);
        }

        $countriesTable = '{{%enupalstripe_countries}}';
        if (!$this->db->tableExists($countriesTable)){
            $this->createTable('{{%enupalstripe_countries}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'iso' => $this->string(2)->notNull(),
                'isStateRequired' => $this->boolean(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%enupalstripe_countries}}', 'name', true);
            $this->createIndex(null, '{{%enupalstripe_countries}}', 'iso', true);
        }

        $ordersTable = '{{%enupalstripe_orders}}';
        if (!$this->db->columnExists($ordersTable, 'billingAddressId')) {
            $this->addColumn($ordersTable, 'billingAddressId', $this->integer()->after('testMode'));
            $this->createIndex(null, $ordersTable, 'billingAddressId', false);
        }

        if (!$this->db->columnExists($ordersTable, 'shippingAddressId')) {
            $this->addColumn($ordersTable, 'shippingAddressId', $this->integer()->after('testMode'));
            $this->createIndex(null, $ordersTable, 'shippingAddressId', false);
        }

        $this->addForeignKey(null, $addressTable, ['countryId'], $countriesTable, ['id'], 'SET NULL');
        $this->addForeignKey(null, $ordersTable, ['billingAddressId'], $addressTable, ['id'], 'SET NULL');
        $this->addForeignKey(null, $ordersTable, ['shippingAddressId'], $addressTable, ['id'], 'SET NULL');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190125_000000_address_tables cannot be reverted.\n";

        return false;
    }
}
