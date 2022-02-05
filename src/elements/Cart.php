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
use enupal\stripe\elements\db\CartQuery;
use enupal\stripe\records\Cart as CartRecord;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\Stripe;

/**
 * Cart represents a entry element.
 */
class Cart extends Element
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';

    // General - Properties
    // =========================================================================
    public $id;
    public $number;
    public $stripeId;
    public $totalPrice;
    public $itemCount;
    public $currency;
    public $items;
    public $cartMetadata;
    public $userEmail;
    public $userId;
    public $cartStatus;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Cart');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'cart';
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
    public static function hasStatuses(): bool
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
            'enupal-stripe/carts/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->number;
    }

    /**
     * @inheritdoc
     *
     * @return CartQuery The newly created [[CartQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new CartQuery(get_called_class());
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
                'label' => StripePlugin::t('All Carts'),
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

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => StripePlugin::t('Are you sure you want to delete the selected carts?'),
            'successMessage' => StripePlugin::t('Carts deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['number', 'stripeId'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'dateCreated' => StripePlugin::t('Created at')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['number'] = ['label' => StripePlugin::t('Number')];
        $attributes['stripeId'] = ['label' => StripePlugin::t('Stripe Id')];
        $attributes['itemCount'] = ['label' => StripePlugin::t('item Count')];
        $attributes['total_price'] = ['label' => StripePlugin::t('Total Price')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['number', 'stripeId', 'itemCount', 'totalPrice'];

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
                return $this->getTotalPrice();
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
        // Get the Cart record
        if (!$isNew) {
            $record = CartRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Cart ID: '.$this->id);
            }
        } else {
            $record = new CartRecord();
            $record->id = $this->id;
        }

        $record->stripeId = $this->stripeId;
        $record->itemCount = $this->itemCount;
        $record->userId = $this->userId;
        $record->cartStatus = $this->cartStatus;
        $record->currency = $this->currency;
        $record->userEmail = $this->userEmail;
        $record->number = $this->number;
        $record->totalPrice = $this->totalPrice;
        $record->items = json_encode($this->getItems());
        $record->cartMetadata = json_encode($this->getCartMetadata());
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [];
        $rules[] = [['items'], 'required'];

        return $rules;
    }

    public function getTotalPrice()
    {
        $totalPrice = StripePlugin::$app->orders->convertFromCents($this->totalPrice, $this->currency);

        return Craft::$app->getFormatter()->asCurrency($totalPrice, $this->currency);
    }

    public function getItems()
    {
        if (is_string($this->items)) {
            $this->items = json_decode($this->items);
        }

        return $this->items;
    }

    public function getCartMetadata()
    {
        if (is_string($this->cartMetadata)) {
            $this->cartMetadata = json_decode($this->cartMetadata);
        }

        return $this->cartMetadata;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return [
          "number" => $this->number,
          "metadata" => $this->getCartMetadata(),
          "total_price" => $this->totalPrice,
          "currency" => $this->currency,
          "item_count" => $this->itemCount,
          "items" => $this->getItems()
        ];
    }
}