<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use enupal\stripe\elements\actions\SetStatus;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\stripe\elements\db\ConnectQuery;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\models\Address;
use enupal\stripe\records\Connect as ConnectRecord;
use enupal\stripe\Stripe as StripePaymentsPlugin;
use craft\validators\UniqueValidator;
use enupal\stripe\Stripe;

/**
 * Connect represents a entry element.
 */
class Connect extends Element
{
    // General - Properties
    // =========================================================================
    public $id;
    public $vendorId;
    public $products;
    public $productType;
    public $allProducts;
    public $rate;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePaymentsPlugin::t('Connect');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'connect';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl(
            'enupal-stripe/connect/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     *
     * @return ConnectQuery The newly created [[ConnectQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new ConnectQuery(get_called_class());
    }

    /**
     * @inheritdoc
     * @param string|null $context
     * @return array
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => StripePaymentsPlugin::t('All Connects'),
            ]
        ];

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => StripePaymentsPlugin::t('Are you sure you want to delete the selected orders?'),
            'successMessage' => StripePaymentsPlugin::t('Connects deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['vendorId', 'products'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'rate' => StripePaymentsPlugin::t('Rate')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['vendorId'] = ['label' => StripePaymentsPlugin::t('Vendor')];
        $attributes['product'] = ['label' => StripePaymentsPlugin::t('Product')];
        $attributes['productTypes'] = ['label' => StripePaymentsPlugin::t('Product Types')];
        $attributes['rate'] = ['label' => StripePaymentsPlugin::t('Rate')];
        $attributes['allProducts'] = ['label' => StripePaymentsPlugin::t('All Products')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['vendorId', 'product', 'productTypes', 'rate', 'allProducts'];

        return $attributes;
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'itemName':
            {
                //return $this->getPaymentForm()->name;
            }

        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function afterSave(bool $isNew)
    {
        // Get the Connect record
        if (!$isNew) {
            $record = ConnectRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Connect ID: '.$this->id);
            }
        } else {
            $record = new ConnectRecord();
            $record->id = $this->id;
        }

        $record->number = $this->number;
        $record->userId = $this->userId;
        $record->orderStatusId = $this->orderStatusId;
        $record->currency = $this->currency;
        $record->totalPrice = $this->totalPrice;
        $record->formId = $this->formId;
        $record->quantity = $this->quantity;
        $record->stripeTransactionId = $this->stripeTransactionId;
        $record->email = $this->email;
        $record->isCompleted = $this->isCompleted;
        $record->shipping = $this->shipping;
        $record->tax = $this->tax;
        $record->couponCode = $this->couponCode;
        $record->couponName = $this->couponName;
        $record->couponAmount = $this->couponAmount;
        $record->couponSnapshot = $this->couponSnapshot;
        $record->variants = $this->variants;
        $record->transactionInfo = $this->transactionInfo;
        $record->testMode = $this->testMode;
        $record->paymentType = $this->paymentType;
        $record->postData = $this->postData;
        $record->message = $this->message;
        $record->subscriptionStatus = $this->subscriptionStatus;
        $record->refunded = $this->refunded;
        $record->dateRefunded = $this->dateRefunded;
        $record->isSubscription = $this->isSubscription;
        $record->billingAddressId = $this->billingAddressId;
        $record->shippingAddressId = $this->shippingAddressId;
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['number'], 'required'],
            [['number'], UniqueValidator::class, 'targetClass' => ConnectRecord::class],
        ];
    }

    /**
     * @return PaymentForm|null
     */
    public function getPaymentForm()
    {
        $paymentForm = StripePaymentsPlugin::$app->paymentForms->getPaymentFormById($this->formId);

        return $paymentForm;
    }

    /**
     * @return mixed
     */
    public function getBasePrice()
    {
        $price = $this->totalPrice - $this->tax - $this->shipping;

        return $price;
    }

    /**
     * @return string
     */
    public function getUserHtml()
    {
        $html = Craft::t("enupal-stripe","Guest");
        if ($this->userId) {
            $user = Craft::$app->getUsers()->getUserById($this->userId);

            if ($user) {
                $html = "<a href='".UrlHelper::cpUrl('users/'.$user->id)."'>".$user->username."</a>";
            }
        }

        return $html;
    }

    /**
     * @return array|mixed
     */
    public function getFormFields()
    {
        $variants = [];

        if ($this->variants){
            $variants = json_decode($this->variants, true);
        }

        return $variants;
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function getShippingAddress()
    {
        $addressHtml = '';

        if ($this->shippingAddressId){
            $address = StripePaymentsPlugin::$app->addresses->getAddressById($this->shippingAddressId);
            $addressHtml = $address->getAddressAsHtml();
        }

        return $addressHtml;
    }

    /**
     * @return Address|null
     */
    public function getShippingAddressModel()
    {
        $address = null;

        if ($this->shippingAddressId){
            $address = StripePaymentsPlugin::$app->addresses->getAddressById($this->shippingAddressId);
        }

        return $address;
    }

    /**
     * @return Address|null
     */
    public function getBillingAddressModel()
    {
        $address = null;

        if ($this->billingAddressId){
            $address = StripePaymentsPlugin::$app->addresses->getAddressById($this->billingAddressId);
        }

        return $address;
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\SyntaxError
     */
    public function getBillingAddress()
    {
        $addressHtml = '';

        if ($this->billingAddressId){
            $address = StripePaymentsPlugin::$app->addresses->getAddressById($this->billingAddressId);
            $addressHtml = $address->getAddressAsHtml();
        }

        return $addressHtml;
    }

    /**
     * @return array
     * @throws \craft\errors\DeprecationException
     */
    public function getShippingAddressAsArray()
    {
        Craft::$app->getDeprecator()->log('Connect::getShippingAddressAsArray()', 'enupal\\stripe\\elements\Connect::getShippingAddressAsArray() has been deprecated. Use getBillingAddressModel() instead.');
        $addressModel = $this->getShippingAddressModel();
        $address = [];

        if ($addressModel){
            $address['addressName'] = $addressModel->getFullName();
            $address['addressStreet'] = $addressModel->address1;
            $address['addressCity'] = $addressModel->city;
            $address['addressState'] = $addressModel->stateName;
            $address['addressZip'] = $addressModel->zipCode;
            $address['addressCountry'] = $addressModel->getCountryText();
        }

        return $address;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        $type = StripePaymentsPlugin::t("One-Time");

        if ($this->isSubscription()){
            $type = StripePaymentsPlugin::t("Subscription");
        }

        return $type;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        $paymentMethod = 'Card';
        if ($this->paymentType != null){
            $paymentMethod = StripePaymentsPlugin::$app->paymentForms->getPaymentTypeName($this->paymentType);
        }

        return $paymentMethod;
    }

    /**
     * @return string
     */
    public function getPaymentStatus()
    {
        $status = $this->isCompleted ? 'succeeded' : 'pending';

        if ($this->refunded){
            $status = 'refunded';
        }

        return $status;
    }

    /**
     * @return string
     */
    public function getStatusHtml()
    {
        $status = StripePaymentsPlugin::$app->orderStatuses->getConnectStatusById($this->orderStatusId);
        $color = $status->color;

        $html = "<span><span class='status ".$color."'> </span>".$status->name.'</span>';

        return $html;
    }

    /**
     * @return string
     */
    public function getPaymentStatusHtml()
    {
        $statuses = [
            'succeeded' => 'green',
            'pending' => 'white',
            'refunded' => 'black',
        ];

        $status = $this->getPaymentStatus();
        $color = $statuses[$status] ?? '';

        $html = "<span class='status ".$color."'> </span>".$status;

        return $html;
    }

    /**
     * @return bool
     */
    public function isSubscription()
    {
        $transactionId = substr($this->stripeTransactionId, 0, 3);

        if ($transactionId != 'sub'){
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function getSubscriptionStatusHtml()
    {
        $html = '';

        if ($this->isSubscription()){
            $subscription = $this->getSubscription();
            $status = $subscription->status;
            $html = StripePaymentsPlugin::$app->subscriptions->getSubscriptionStatusHtml($status);
        }

        return $html;
    }

    /**
     * @return \enupal\stripe\models\Subscription|null
     */
    public function getSubscription()
    {
        $subscription = null;

        if ($this->isSubscription()){
            $subscription = StripePaymentsPlugin::$app->subscriptions->getSubscriptionModel($this->stripeTransactionId);
        }

        return $subscription;
    }

    /**
     * @param string $fieldHandle
     * @param $value
     */
    public function setFormFieldValue(string $fieldHandle, $value)
    {
        $variants = $this->getFormFields();

        if (isset($variants[$fieldHandle])){
            $variants[$fieldHandle] =  $value;
        }

        $this->variants = json_encode($variants);
    }

    /**
     * @param array $values
     */
    public function setFormFieldValues(array $values)
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFormFieldValue($fieldHandle, $value);
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getNeedCapture()
    {
        try{
            if ($this->paymentType == PaymentType::CC || is_null($this->paymentType ) && !$this->isCompleted){
                $stripeId = Stripe::$app->orders->getChargeIdFromConnect($this);
                $charge = Stripe::$app->orders->getCharge($stripeId);

                return !$charge->captured;
            }
        }catch (\Exception $e){
            Craft::error($e->getMessage(), __METHOD__);
        }

        return false;
    }
}