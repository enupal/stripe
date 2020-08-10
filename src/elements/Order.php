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

use enupal\stripe\elements\db\OrdersQuery;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\models\Address;
use enupal\stripe\records\Order as OrderRecord;
use enupal\stripe\Stripe as StripePaymentsPlugin;
use craft\validators\UniqueValidator;
use enupal\stripe\Stripe;

/**
 * Order represents a entry element.
 */
class Order extends Element
{
    // General - Properties
    // =========================================================================
    public $id;

    public $testMode;

    public $userId;

    public $paymentType;

    /**
     * @var string Number
     */
    public $number;

    /**
     * @var string Stripe Transaction Id
     */
    public $stripeTransactionId;

    /**
     * @var string Stripe Transaction Info
     */
    public $transactionInfo;

    /**
     * @var int Number
     */
    public $quantity;

    /**
     * @var int Order Status Id
     */
    public $orderStatusId;

    public $formId;
    public $currency;
    public $totalPrice;
    public $shipping;
    public $tax;
    public $isCompleted;
    public $email;
    public $firstName;
    public $lastName;
    // Shipping
    public $billingAddressId;
    public $shippingAddressId;
    public $addressCity;
    public $addressCountry;
    public $addressState;
    public $addressCountryCode;
    public $addressName;
    public $addressStreet;
    public $addressZip;
    // coupons
    public $couponCode;
    public $couponName;
    public $couponAmount;
    public $couponSnapshot;
    // variants
    public $variants;
    public $postData;
    public $message;
    // refund
    public $refunded;
    public $dateRefunded;
    // subscriptions
    public $subscriptionStatus;
    public $isSubscription;

    public $dateCreated;
    public $dateOrdered;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePaymentsPlugin::t('Orders');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'orders';
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
            'enupal-stripe/orders/edit/'.$this->id
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
        return $this->number;
    }

    /**
     * @inheritdoc
     *
     * @return OrdersQuery The newly created [[OrdersQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new OrdersQuery(get_called_class());
    }

    /**
     *
     * @return string|null
     */
    public function getStatus()
    {
        $statusId = $this->orderStatusId;

        $status = StripePaymentsPlugin::$app->orderStatuses->getOrderStatusById($statusId);

        return $status->handle;
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
                'label' => StripePaymentsPlugin::t('All Orders'),
            ]
        ];

        $statuses = StripePaymentsPlugin::$app->orderStatuses->getAllOrderStatuses();

        $sources[] = ['heading' => StripePaymentsPlugin::t("Order Status")];

        foreach ($statuses as $status) {
            $key = 'orderStatusId:'.$status->id;
            $sources[] = [
                'status' => $status->color,
                'key' => $key,
                'label' => ucwords(strtolower($status->name)),
                'criteria' => ['orderStatusHandle' => $status->handle]
            ];
        }

        $sources[] = ['heading' => StripePaymentsPlugin::t("Payment Type")];

        $sources[] = [
            'key' => 'oneTime:1',
            'label' => Craft::t('enupal-stripe', 'One-Time'),
            'criteria' => ['isSubscription' => false]
        ];

        $sources[] = [
            'key' => 'subscriptions:1',
            'label' => Craft::t('enupal-stripe', 'Subscriptions'),
            'criteria' => ['isSubscription' => true]
        ];

        $sources[] = ['heading' => StripePaymentsPlugin::t("Payment Status")];

        $sources[] = [
            'key' => 'paymentStatus:1',
            'label' => Craft::t('enupal-stripe', 'Succeeded'),
            'criteria' => ['isCompleted' => true, 'refunded' => false]
        ];

        $sources[] = [
            'key' => 'paymentStatus:2',
            'label' => Craft::t('enupal-stripe', 'Pending'),
            'criteria' => ['isCompleted' => false]
        ];

        $sources[] = [
            'key' => 'paymentStatus:3',
            'label' => Craft::t('enupal-stripe', 'Refunded'),
            'criteria' => ['refunded' => true]
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
            'successMessage' => StripePaymentsPlugin::t('Orders deleted.'),
        ]);

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => SetStatus::class,
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['number', 'stripeTransactionId', 'currency', 'email'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'dateOrdered' => StripePaymentsPlugin::t('Date Ordered'),
            'number' => StripePaymentsPlugin::t('Order Number'),
            'totalPrice' => StripePaymentsPlugin::t('Total'),
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['number'] = ['label' => StripePaymentsPlugin::t('Order Number')];
        $attributes['status'] = ['label' => StripePaymentsPlugin::t('Status')];
        $attributes['totalPrice'] = ['label' => StripePaymentsPlugin::t('Total')];
        $attributes['paymentStatus'] = ['label' => StripePaymentsPlugin::t('Payment Status')];
        $attributes['user'] = ['label' => StripePaymentsPlugin::t('User')];
        $attributes['address'] = ['label' => StripePaymentsPlugin::t('Shipping Address')];
        $attributes['billingAddress'] = ['label' => StripePaymentsPlugin::t('Billing Address')];
        $attributes['email'] = ['label' => StripePaymentsPlugin::t('Customer Email')];
        $attributes['itemName'] = ['label' => StripePaymentsPlugin::t('Item Name')];
        $attributes['itemSku'] = ['label' => StripePaymentsPlugin::t('Form Handle')];
        $attributes['stripeTransactionId'] = ['label' => StripePaymentsPlugin::t('Stripe Transaction Id')];
        $attributes['dateOrdered'] = ['label' => StripePaymentsPlugin::t('Date Ordered')];
        $attributes['paymentType'] = ['label' => StripePaymentsPlugin::t('Payment Type')];
        $attributes['currency'] = ['label' => StripePaymentsPlugin::t('Currency')];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateOrdered';
        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['number', 'orderStatusId', 'itemName', 'itemSku', 'totalPrice', 'email', 'user', 'paymentStatus', 'dateOrdered'];

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
            case 'totalPrice':
                {
                    if ($this->$attribute >= 0) {
                        return Craft::$app->getFormatter()->asCurrency($this->$attribute, $this->currency);
                    }

                    return Craft::$app->getFormatter()->asCurrency($this->$attribute * -1, $this->currency);
                }
            case 'address':
                {
                    return $this->getShippingAddress();
                }
            case 'billingAddress':
                {
                    return $this->getBillingAddress();
                }
            case 'paymentStatus':
                {
                    return $this->getPaymentStatusHtml();
                }
            case 'status':
                {
                    return $this->getStatusHtml();
                }
            case 'user':
                {
                    return $this->getUserHtml();
                }
            case 'email':
                {
                    return '<a href="mailto:'.$this->email.'">'.$this->email.'</a>';
                }
            case 'itemName':
                {
                    return $this->getPaymentForm()->name;
                }
            case 'itemSku':
                {
                    return '<a href="'.$this->getPaymentForm()->getCpEditUrl().'">'.$this->getPaymentForm()->handle.'</a>';
                }
            case 'paymentType':
                {
                    return $this->getPaymentType();
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
        // Get the Order record
        if (!$isNew) {
            $record = OrderRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Order ID: '.$this->id);
            }
        } else {
            $record = new OrderRecord();
            $record->id = $this->id;
        }

        $record->dateOrdered = $this->dateOrdered ?? Db::prepareDateForDb(new \DateTime());
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
            [['number'], UniqueValidator::class, 'targetClass' => OrderRecord::class],
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

        if ($user = $this->getUser()) {
            $html = "<a href='".UrlHelper::cpUrl('users/'.$user->id)."'>".$user->username."</a>";
        }

        return $html;
    }

    public function getUser()
    {
        if ($this->userId) {
            $user = Craft::$app->getUsers()->getUserById($this->userId);

            if ($user) {
                return $user;
            }
        }

        return null;
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
        Craft::$app->getDeprecator()->log('Order::getShippingAddressAsArray()', 'enupal\\stripe\\elements\Order::getShippingAddressAsArray() has been deprecated. Use getBillingAddressModel() instead.');
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
        $status = StripePaymentsPlugin::$app->orderStatuses->getOrderStatusById($this->orderStatusId);
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
                $stripeId = Stripe::$app->orders->getChargeIdFromOrder($this);
                $charge = Stripe::$app->orders->getCharge($stripeId);

                return !$charge->captured;
            }
        }catch (\Exception $e){
            Craft::error($e->getMessage(), __METHOD__);
        }

        return false;
    }
}