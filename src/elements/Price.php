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
use enupal\stripe\elements\db\PriceQuery;
use enupal\stripe\records\Price as PriceRecord;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\Stripe;

/**
 * Price represents a entry element.
 */
class Price extends Element
{
    // General - Properties
    // =========================================================================
    public ?int $id;
    public $stripeId;
    public $productId;
    public $stripeObject;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Price');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'price';
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
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl(
            'enupal-stripe/prices/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        $name = $this->getUnitAmount() ?? $this->stripeId;

        return (string)$name;
    }

    /**
     * @inheritdoc
     *
     * @return PriceQuery The newly created [[PriceQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new PriceQuery(get_called_class());
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
                'label' => StripePlugin::t('All Prices'),
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

        // Delete @todo update to disabled prices on stripe
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => StripePlugin::t('Are you sure you want to disable the selected prices?'),
            'successMessage' => StripePlugin::t('Prices disabled.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['stripeId'];
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
        $attributes['stripeId'] = ['label' => StripePlugin::t('Stripe Id')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['stripeId', 'unitAmount'];

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
            case 'unitAmount':
            {
                return $this->getUnitAmount();
            }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function afterSave(bool $isNew): void
    {
        // Get the Price record
        if (!$isNew) {
            $record = PriceRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Price ID: '.$this->id);
            }
        } else {
            $record = new PriceRecord();
            $record->id = $this->id;
        }

        $record->stripeId = $this->stripeId;
        $record->productId = $this->productId;
        $record->stripeObject = json_encode($this->getStripeObject());
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = [];
        $rules[] = [['stripeId', 'stripeObject', 'productId'], 'required'];

        return $rules;
    }

    public function getUnitAmount()
    {
        $unitAmount = StripePlugin::$app->orders->convertFromCents($this->getStripeObject()->unit_amount, $this->getStripeObject()->currency);

        return Craft::$app->getFormatter()->asCurrency($unitAmount, $this->getStripeObject()->currency);
    }

    public function getStripeObject()
    {
        if (is_string($this->stripeObject)) {
            $this->stripeObject = json_decode($this->stripeObject);
        }

        return $this->stripeObject;
    }

    /**
     * @return Product|null
     */
    public function getProduct()
    {
        if ($this->productId){
            return StripePlugin::$app->products->getProductById($this->productId);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getStatusHtml()
    {
        $statuses = [
            'active' => 'green',
            'disabled' => 'red'
        ];

        $status = $this->getStripeObject()->active ? 'active' : 'disabled';
        $color = $statuses[$status] ?? '';

        $html = "<span class='status ".$color."'> </span>".$status;

        return $html;
    }
}