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
use craft\records\Element;

/**
 * Class Order record.
 * @property $id
 * @property $dateOrdered
 * @property $userId
 * @property $number
 * @property $orderStatusId
 * @property $currency
 * @property $totalPrice
 * @property $formId
 * @property $quantity
 * @property $stripeTransactionId
 * @property $email
 * @property $isCompleted
 * @property $shipping
 * @property $tax
 * @property $variants
 * @property $transactionInfo
 * @property $testMode
 * @property $paymentType
 * @property $postData
 * @property $message
 * @property $subscriptionStatus
 * @property $refunded
 * @property $dateRefunded
 * @property $isSubscription
 * @property $billingAddressId
 * @property $shippingAddressId
 * @property $couponCode;
 * @property $couponName;
 * @property $couponAmount;
 * @property $couponSnapshot;
 */
class Order extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_orders}}';
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}