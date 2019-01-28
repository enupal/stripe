<?php

namespace enupal\stripe\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m190127_000000_insert_addresses migration.
 */
class m190127_000000_insert_addresses extends Migration
{
    /**
     * @return bool
     */
    public function safeUp()
    {
        $addressData = [];
        $ordersTable = '{{%enupalstripe_orders}}';

        $orders = (new Query())
            ->select(['*'])
            ->from([$ordersTable])
            ->all();

        foreach ($orders as $order) {
            if ($order['addressCountry'] || $order['addressCity'] || $order['addressStreet']){
                $countryId = $this->getCountryId($order['addressCountry']);

                if (is_null($countryId)){
                    $countryId = $this->getDefaultCountryId();
                }

                $addressData['countryId'] = $countryId;
                $addressData['address1'] = $order['addressStreet'];
                $addressData['city'] = $order['addressCity'];
                $addressData['zipCode'] = $order['addressZip'];
                $addressData['stateName'] = $order['addressState'];
                $addressData['firstName'] = $order['addressName'];

                $this->insert('{{%enupalstripe_addresses}}', $addressData);
                $addressId = $this->db->getLastInsertID();

                $this->update($ordersTable, [
                    'billingAddressId' => $addressId,
                    'shippingAddressId' => $addressId
                ], [
                    'id' => $order['id']
                ], [], false);
            }
        }

        return true;
    }

    /**
     * @param $country
     * @return int|null
     */
    private function getCountryId($country)
    {
        $countryResult = (new Query())
            ->select(['*'])
            ->where(['iso' => $country])
            ->orWhere(['name' => $country])
            ->from(['{{%enupalstripe_countries}}'])
            ->one();

         return $countryResult['id'] ?? null;
    }

    /**
     * @return int|null
     */
    private function getDefaultCountryId()
    {
        return $this->getCountryId('US');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190127_000000_insert_addresses cannot be reverted.\n";

        return false;
    }
}
