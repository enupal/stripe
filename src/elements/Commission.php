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
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;
use enupal\stripe\elements\db\CommissionsQuery;
use enupal\stripe\records\Commission as CommissionRecord;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\Stripe;

/**
 * Commission represents a entry element.
 */
class Commission extends Element
{
    // General - Properties
    // =========================================================================
    public $id;
    public $orderId;
    public $productId;
    public $connectId;
    public $stripeId;
    public $number;
    public $orderType;
    public $commissionStatus;
    public $totalPrice;
    public $testMode;
    public $currency;
    public $datePaid;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Commissions');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'commission';
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
            'enupal-stripe/commissions/edit/'.$this->id
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
     * @return CommissionsQuery The newly created [[CommissionsQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new CommissionsQuery(get_called_class());
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
                'label' => StripePlugin::t('All Commissions'),
            ]
        ];

        $sources[] = ['heading' => StripePlugin::t("Transfer Status")];

        $sources[] = [
            'key' => 'paymentStatus:1',
            'label' => Craft::t('enupal-stripe', 'Paid'),
            'criteria' => ['commissionStatus' => 'paid']
        ];

        $sources[] = [
            'key' => 'paymentStatus:2',
            'label' => Craft::t('enupal-stripe', 'Pending'),
            'criteria' => ['commissionStatus' => 'pending']
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
            'confirmationMessage' => StripePlugin::t('Are you sure you want to delete the selected commissions?'),
            'successMessage' => StripePlugin::t('Commissions deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['number','orderId', 'connectId', 'stripeId', 'datePaid'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'dateCreated' => StripePlugin::t('Date Created'),
            'datePaid' => StripePlugin::t('Date Paid'),
            'number' => StripePlugin::t('Commission Number'),
            'totalPrice' => StripePlugin::t('Total Price'),
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['number'] = ['label' => StripePlugin::t('Number')];
        $attributes['totalPrice'] = ['label' => StripePlugin::t('Total')];
        $attributes['stripeId'] = ['label' => StripePlugin::t('Stripe Id')];
        $attributes['orderType'] = ['label' => StripePlugin::t('Order Type')];
        $attributes['orderId'] = ['label' => StripePlugin::t('Order')];
        $attributes['productId'] = ['label' => StripePlugin::t('Product')];
        $attributes['connectId'] = ['label' => StripePlugin::t('Connect')];
        $attributes['commissionStatus'] = ['label' => StripePlugin::t('Status')];
        $attributes['datePaid'] = ['label' => StripePlugin::t('Date Paid')];
        $attributes['dateCreated'] = ['label' => StripePlugin::t('Date Created')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['number','totalPrice', 'stripeId', 'orderType', 'orderId', 'productId', 'connectId', 'commissionStatus', 'datePaid', 'dateCreated'];

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
            case 'orderType':
            {
                return $this->getOderTypeName();
            }
            case 'totalPrice':
            {
                return Craft::$app->getFormatter()->asCurrency($this->$attribute, $this->currency);
            }
            case 'commissionStatus':
            {
                return $this->getPaymentStatusHtml();
            }
            case 'productId':
            {
                return '<a href="'.$this->getProduct()->getCpEditUrl().'">View Product</a>';
            }
            case 'orderId':
            {
                return '<a href="'.$this->getOrder()->getCpEditUrl().'">View Order</a>';
            }
            case 'connectId':
            {
                return '<a href="'.$this->getConnect()->getCpEditUrl().'">View Connect</a>';
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
        // Get the Commission record
        if (!$isNew) {
            $record = CommissionRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Commission ID: '.$this->id);
            }
        } else {
            $record = new CommissionRecord();
            $record->id = $this->id;
        }

        $record->orderId = $this->orderId;
        $record->productId = $this->productId;
        $record->connectId = $this->connectId;
        $record->stripeId = $this->stripeId;
        $record->number = $this->number;
        $record->orderType = $this->orderType;
        $record->commissionStatus = $this->commissionStatus;
        $record->totalPrice = $this->totalPrice;
        $record->datePaid = $this->datePaid;
        $record->currency = $this->currency;
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
            [['orderId', 'productId', 'connectId', 'orderType', 'commissionStatus'], 'required']
        ];
    }

    /**
     * @return \craft\base\ElementInterface|null
     */
    public function getOrder()
    {
        return Craft::$app->getElements()->getElementById($this->orderId, $this->orderType);
    }

    /**
     * @return \craft\base\ElementInterface|null
     */
    public function getProduct()
    {
        return Craft::$app->getElements()->getElementById($this->productId);
    }

    /**
     * @return Connect|null
     */
    public function getConnect()
    {
        if ($this->connectId) {
            return StripePlugin::$app->connects->getConnectById($this->connectId);
        }

        return null;
    }

    /**
     * @return Vendor|null
     */
    public function getVendor()
    {
        $connect = $this->getConnect();
        if ($connect) {
            if ($vendor = $connect->getVendor()) {
                return $vendor;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getOderTypeName()
    {
        return $this->isCommerceOrder() ? 'Commerce' : 'Stripe Payments';
    }

    /**
     * @return bool
     */
    public function isCommerceOrder()
    {
        return strpos($this->orderType, 'commerce') !== false;
    }

    /**
     * @return string
     */
    public function getPaymentStatusHtml()
    {
        $statuses = [
            'paid' => 'green',
            'pending' => 'white'
        ];

        $status = $this->commissionStatus;
        $color = $statuses[$status] ?? '';

        $html = "<span class='status ".$color."'> </span>".$status;

        return $html;
    }
}