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
 * Class Payment Form record.
 * @property int    $id
 * @property string $companyName
 * @property string $name
 * @property string $handle
 * @property bool $enableCheckout
 * @property bool $checkoutCancelUrl
 * @property bool $checkoutSuccessUrl
 * @property bool $checkoutSubmitType
 * @property string $paymentType
 * @property string $checkoutPaymentType
 * @property string $currency
 * @property string $language
 * @property integer $amountType
 * @property float $minimumAmount
 * @property string $customAmountLabel
 * @property float $amount
 * @property string $logoImage
 * @property boolean $enableRememberMe
 * @property boolean $enableRecurringPayment
 * @property string $recurringPaymentType
 * @property boolean $enableSubscriptions
 * @property integer $subscriptionType
 * @property float $singlePlanSetupFee
 * @property integer $singlePlanTrialPeriod
 * @property string $singlePlanInfo
 * @property bool $enableCustomPlanAmount
 * @property float $customPlanMinimumAmount
 * @property float $customPlanDefaultAmount
 * @property integer $customPlanInterval
 * @property string $customPlanFrequency
 * @property string $subscriptionStyle
 * @property string $selectPlanLabel
 * @property integer $quantity
 * @property boolean $hasUnlimitedStock
 * @property boolean $customerQuantity
 * @property string $soldOutMessage
 * @property boolean $verifyZip
 * @property boolean $enableBillingAddress
 * @property boolean $enableShippingAddress
 * @property float $shippingAmount
 * @property float $itemWeight
 * @property string $itemWeightUnit
 * @property boolean $showItemName
 * @property boolean $showItemPrice
 * @property boolean $showItemCurrency
 * @property string $returnUrl
 * @property string $buttonClass
 * @property string $buttonText
 * @property string $paymentButtonProcessingText
 * @property string $checkoutButtonText
 * @property string $enableTemplateOverrides
 * @property string $templateOverridesFolder
 */

class PaymentForm extends ActiveRecord
{
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%enupalstripe_forms}}';
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