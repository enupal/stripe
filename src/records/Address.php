<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Address record.
 *
 * @property string $address1
 * @property string $address2
 * @property string $alternativePhone
 * @property string $attention
 * @property string $businessId
 * @property string $businessName
 * @property string $businessTaxId
 * @property string $city
 * @property Country $country
 * @property int $countryId
 * @property string $firstName
 * @property int $id
 * @property string $lastName
 * @property string $phone
 * @property string $stateName
 * @property string $title
 * @property string $zipCode
 */
class Address extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_addresses}}';
    }

    /**
     * Returns the address's country
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCountry(): ActiveQueryInterface
    {
        return $this->hasOne(Country::class, ['id' => 'countryId']);
    }
}
