<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\events\AddressEvent;
use enupal\stripe\models\Address;
use enupal\stripe\records\Address as AddressRecord;
use craft\db\Query;
use enupal\stripe\Stripe;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class Addresses extends Component
{
     /**
     * @event AddressEvent The event that is raised before an address is saved.
     *
     * Plugins can get notified before an address is being saved
     *
     * ```php
     * use enupal\stripe\events\AddressEvent;
     * use enupal\stripe\services\Addresses;
     * use yii\base\Event;
     *
     * Event::on(Addresses::class, Addresses::EVENT_BEFORE_SAVE_ADDRESS, function(AddressEvent $e) {
     *     // Do something - perhaps let an external CRM system know about a client's new address
     * });
     * ```
     */
    const EVENT_BEFORE_SAVE_ADDRESS = 'beforeSaveAddress';

    /**
     * @event AddressEvent The event that is raised after an address is saved.
     *
     * Plugins can get notified before an address is being saved
     *
     * ```php
     * use enupal\stripe\events\AddressEvent;
     * use enupal\stripe\services\Addresses;
     * use yii\base\Event;
     *
     * Event::on(Addresses::class, Addresses::EVENT_AFTER_SAVE_ADDRESS, function(AddressEvent $e) {
     *     // Do something - perhaps set this address as default in an external CRM system
     * });
     * ```
     */
    const EVENT_AFTER_SAVE_ADDRESS = 'afterSaveAddress';

    /**
     * @var Address[]
     */
    private $_addressesById = [];

    /**
     * Returns an address by its ID.
     *
     * @param int $addressId the address' ID
     * @return Address|null the matched address or null if not found
     */
    public function getAddressById(int $addressId)
    {
        if (array_key_exists($addressId, $this->_addressesById)) {
            return $this->_addressesById[$addressId];
        }

        $result = $this->_createAddressQuery()
            ->where(['id' => $addressId])
            ->one();

        return $this->_addressesById[$addressId] = $result ? new Address($result) : null;
    }

    /**
     * Returns an address by an address id and customer id.
     *
     * @param int $addressId the address id
     * @param int $customerId the customer's ID
     * @return Address|null the matched address or null if not found
     */
    public function getAddressByIdAndCustomerId(int $addressId, $customerId = null)
    {
        $result = $this->_createAddressQuery()
            ->innerJoin('{{%commerce_customers_addresses}} customerAddresses', '[[customerAddresses.addressId]] = [[addresses.id]]')
            ->where(['customerAddresses.customerId' => $customerId])
            ->andWhere(['addresses.id' => $addressId])
            ->one();

        return $this->_addressesById[$addressId] = $result ? new Address($result) : null;
    }

    /**
     * @param Address $addressModel The address to be saved.
     * @param bool $runValidation should we validate this address before saving.
     * @return bool Whether the address was saved successfully.
     */
    public function saveAddress(Address $addressModel, bool $runValidation = true): bool
    {
        $isNewAddress = !$addressModel->id;

        if ($addressModel->id) {
            $addressRecord = AddressRecord::findOne($addressModel->id);

            if (!$addressRecord) {
                throw new InvalidArgumentException('No address exists with the ID “{id}”', ['id' => $addressModel->id]);
            }
        } else {
            $addressRecord = new AddressRecord();
        }

        //Raise the beforeSaveAddress event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ADDRESS)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ADDRESS, new AddressEvent([
                'address' => $addressModel,
                'isNew' => $isNewAddress
            ]));
        }

        if ($runValidation && !$addressModel->validate()) {
            Craft::info('Address could not save due to validation error.', __METHOD__);
            return false;
        }

        $addressRecord->attention = $addressModel->attention;
        $addressRecord->title = $addressModel->title;
        $addressRecord->firstName = $addressModel->firstName;
        $addressRecord->lastName = $addressModel->lastName;
        $addressRecord->address1 = $addressModel->address1;
        $addressRecord->address2 = $addressModel->address2;
        $addressRecord->city = $addressModel->city;
        $addressRecord->zipCode = $addressModel->zipCode;
        $addressRecord->phone = $addressModel->phone;
        $addressRecord->alternativePhone = $addressModel->alternativePhone;
        $addressRecord->businessName = $addressModel->businessName;
        $addressRecord->businessTaxId = $addressModel->businessTaxId;
        $addressRecord->businessId = $addressModel->businessId;
        $addressRecord->countryId = $addressModel->countryId;
        $addressRecord->stateName = $addressModel->stateName;

        $addressRecord->save(false);

        if ($isNewAddress) {
            // Now that we have a record ID, save it on the model
            $addressModel->id = $addressRecord->id;
        }

        //Raise the afterSaveAddress event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ADDRESS)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ADDRESS, new AddressEvent([
                'address' => $addressModel,
                'isNew' => $isNewAddress
            ]));
        }

        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteAddressById(int $id): bool
    {
        $address = AddressRecord::findOne($id);

        if (!$address) {
            return false;
        }

        return (bool)$address->delete();
    }

    /**
     * Returns a Query object prepped for retrieving addresses.
     *
     * @return Query The query object.
     */
    private function _createAddressQuery(): Query
    {
        return (new Query())
            ->select([
                'addresses.id',
                'addresses.attention',
                'addresses.title',
                'addresses.firstName',
                'addresses.lastName',
                'addresses.countryId',
                'addresses.address1',
                'addresses.address2',
                'addresses.city',
                'addresses.zipCode',
                'addresses.phone',
                'addresses.alternativePhone',
                'addresses.businessName',
                'addresses.businessTaxId',
                'addresses.businessId',
                'addresses.stateName'
            ])
            ->from(['{{%enupalstripe_addresses}} addresses']);
    }

    /**
     * @param $data
     * @return int|null
     */
    public function getAddressIdFromCharge($data)
    {
        $orderId = null;
        $address = new Address();
        $address->firstName = $data['name'] ?? null;
        $address->city = $data['address_city'] ?? null;
        $countryId = null;

        if (isset($data['address_country']) && $data['address_country']){
            $country = Stripe::$app->countries->getCountryByIso($data['address_country']);
            if ($country){
                $countryId = $country->id;
            }
        }

        $address->countryId = $countryId;
        $address->stateName = $data['address_state'] ?? null;
        $address->address1 = $data['address_line1'] ?? null;
        $address->zipCode = $data['address_zip'] ?? null;

        if (Stripe::$app->addresses->saveAddress($address, true)){
            $orderId = $address->id;
        }

        return $orderId;
    }

    /**
     * @param $data
     * @return int|null
     */
    public function getNewAddressIdFromCharge($data)
    {
        $orderId = null;
        $address = new Address();
        $address->firstName = $data['name'] ?? null;
        $countryId = null;

        if (isset($data['address']['country']) && $data['address']['country']){
            $country = Stripe::$app->countries->getCountryByIso($data['address']['country']);
            if ($country){
                $countryId = $country->id;
            }
        }

        $address->countryId = $countryId;
        $address->city = $data['address']['city'] ?? null;
        $address->stateName = $data['address']['state'] ?? null;
        $address->address1 = $data['address']['line1'] ?? null;
        $address->zipCode = $data['address']['postal_code'] ?? null;

        if (Stripe::$app->addresses->saveAddress($address, true)){
            $orderId = $address->id;
        }

        return $orderId;
    }
}
