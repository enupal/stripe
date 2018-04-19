<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use yii\base\ErrorHandler;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\stripe\elements\db\OrdersQuery;
use enupal\stripe\records\Order as OrderRecord;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\Stripe as PaypalPlugin;
use craft\validators\UniqueValidator;

/**
 * Order represents a entry element.
 */
class Order extends Element
{
    // General - Properties
    // =========================================================================
    public $id;

    public $testMode;

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
    public $orderStatusId = OrderStatus::NEW;

    public $buttonId;
    public $currency;
    public $totalPrice;
    public $shipping;
    public $tax;
    public $discount;
    public $email;
    public $firstName;
    public $lastName;
    // Shipping
    public $addressCity;
    public $addressCountry;
    public $addressState;
    public $addressCountryCode;
    public $addressName;
    public $addressStreet;
    public $addressZip;
    // variants
    public $variants;

    public $dateCreated;
    public $dateOrdered;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return PaypalPlugin::t('Orders');
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
    public static function hasStatuses(): bool
    {
        return true;
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
        try {
            return $this->number;
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
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
        $statusId = $this->orderStatusId ?? OrderStatus::NEW;

        $colors = PaypalPlugin::$app->orders->getColorStatuses();

        return $colors[$statusId];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => PaypalPlugin::t('All Orders'),
            ]
        ];

        $statuses = OrderStatus::getConstants();

        $colors = PaypalPlugin::$app->orders->getColorStatuses();

        $sources[] = ['heading' => PaypalPlugin::t("Order Status")];

        foreach ($statuses as $code => $status) {
            $key = 'orderStatusId:'.$status;
            $sources[] = [
                'status' => $colors[$status],
                'key' => $key,
                'label' => ucwords(strtolower($code)),
                'criteria' => ['orderStatusId' => $status]
            ];
        }

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
            'confirmationMessage' => PaypalPlugin::t('Are you sure you want to delete the selected orders?'),
            'successMessage' => PaypalPlugin::t('Orders deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['number', 'stripeTransactionId'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'dateOrdered' => PaypalPlugin::t('Date Ordered'),
            'number' => PaypalPlugin::t('Order Number'),
            'totalPrice' => PaypalPlugin::t('Total'),
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['number'] = ['label' => PaypalPlugin::t('Order Number')];
        $attributes['totalPrice'] = ['label' => PaypalPlugin::t('Total')];
        $attributes['firstName'] = ['label' => PaypalPlugin::t('Amount')];
        $attributes['lastName'] = ['label' => PaypalPlugin::t('Amount')];
        $attributes['address'] = ['label' => PaypalPlugin::t('Shipping Address')];
        $attributes['email'] = ['label' => PaypalPlugin::t('Customer Email')];
        $attributes['itemName'] = ['label' => PaypalPlugin::t('Item Name')];
        $attributes['itemSku'] = ['label' => PaypalPlugin::t('Item SKU')];
        $attributes['stripeTransactionId'] = ['label' => PaypalPlugin::t('PayPal Transaction Id')];
        $attributes['dateOrdered'] = ['label' => PaypalPlugin::t('Date Ordered')];

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
        $attributes = ['number', 'itemName', 'itemSku', 'totalPrice', 'email', 'dateOrdered'];

        return $attributes;
    }

    /**
     * @inheritdoc
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
            case 'email':
                {
                    return '<a href="mailto:'.$this->email.'">'.$this->email.'</a>';
                }
            case 'itemName':
                {
                    return $this->getButton()->name;
                }
            case 'itemSku':
                {
                    return '<a href="'.$this->getButton()->getCpEditUrl().'">'.$this->getButton()->sku.'</a>';
                }

        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
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

        $record->dateOrdered = Db::prepareDateForDb(new \DateTime());
        $record->number = $this->number;
        $record->orderStatusId = $this->orderStatusId;
        $record->currency = $this->currency;
        $record->totalPrice = $this->totalPrice;
        $record->buttonId = $this->buttonId;
        $record->quantity = $this->quantity;
        $record->stripeTransactionId = $this->stripeTransactionId;
        $record->email = $this->email;
        $record->firstName = $this->firstName;
        $record->lastName = $this->lastName;
        $record->shipping = $this->shipping;
        $record->tax = $this->tax;
        $record->discount = $this->discount;
        $record->addressCity = $this->addressCity;
        $record->addressCountry = $this->addressCountry;
        $record->addressState = $this->addressState;
        $record->addressCountryCode = $this->addressCountryCode;
        $record->addressName = $this->addressName;
        $record->addressStreet = $this->addressStreet;
        $record->addressZip = $this->addressZip;
        $record->variants = $this->variants;
        $record->transactionInfo = $this->transactionInfo;
        $record->testMode = $this->testMode;
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
     * @return \craft\base\ElementInterface|null
     */
    public function getButton()
    {
        $button = PaypalPlugin::$app->buttons->getButtonById($this->buttonId);

        return $button;
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
    public function getStatusName()
    {
        $statuses = OrderStatus::getConstants();

        $statuses = array_flip($statuses);

        return ucwords(strtolower($statuses[$this->orderStatusId]));
    }

    /**
     * @return array|mixed
     */
    public function getVariants()
    {
        $variants = [];

        if ($this->variants){
            $variants = json_decode($this->variants, true);
        }

        return $variants;
    }

    /**
     * @return string
     */
    public function getShippingAddress()
    {
        $address = "<address>{{ order.addressName }}<br>{{ order.addressStreet }}<br>{{ order.addressCity }}, {{ order.addressState }} {{ order.addressZip }}<br>{{ order.addressCountry }}</address>";
        $address = Craft::$app->getView()->renderString($address, ['order'=>$this]);

        return $address;
    }
}