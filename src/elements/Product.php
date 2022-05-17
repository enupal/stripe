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
use enupal\stripe\elements\actions\DisableProducts;
use enupal\stripe\elements\actions\EnableProducts;
use enupal\stripe\elements\db\ProductQuery;
use enupal\stripe\records\Product as ProductRecord;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\Stripe;

/**
 * Product represents a entry element.
 */
class Product extends Element
{
    // General - Properties
    // =========================================================================
    public ?int $id;
    public $stripeId;
    public $stripeObject;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Product');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    protected function isEditable(): bool
    {
        return true;
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
            'enupal-stripe/products/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        $name = $this->getStripeObject()->name ?? $this->stripeId;

        return (string)$name;
    }

    /**
     * @inheritdoc
     *
     * @return ProductQuery The newly created [[ProductQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new ProductQuery(get_called_class());
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
                'label' => StripePlugin::t('All Products'),
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
            'type' => DisableProducts::class
        ]);

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => EnableProducts::class
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
            'dateCreated' => StripePlugin::t('Created at'),
            'dateUpdated' => StripePlugin::t('Updated at'),
            'stripeId' => StripePlugin::t('Stripe Id')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['name'] = ['label' => StripePlugin::t('Name')];
        $attributes['stripeId'] = ['label' => StripePlugin::t('Stripe Id')];
        $attributes['prices'] = ['label' => StripePlugin::t('Number of Prices')];
        $attributes['dateCreated'] = ['label' => StripePlugin::t('Date Created')];
        $attributes['dateUpdated'] = ['label' => StripePlugin::t('Date Updated')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['name', 'stripeId', 'prices', 'dateCreated', 'dateUpdated'];

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
            case 'name':
            {
                return $this->getStripeObject()->name;
            }
            case 'prices':
            {
                return count($this->getPrices());
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
        // Get the Product record
        if (!$isNew) {
            $record = ProductRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Product ID: '.$this->id);
            }
        } else {
            $record = new ProductRecord();
            $record->id = $this->id;
        }

        $record->stripeId = $this->stripeId;
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
        $rules[] = [['stripeId', 'stripeObject'], 'required'];

        return $rules;
    }

    /**
     * @return mixed
     */
    public function getStripeObject()
    {
        if (is_string($this->stripeObject)) {
            $this->stripeObject = json_decode($this->stripeObject);
        }

        return $this->stripeObject;
    }

    /**
     * @param string|null $status
     * @return array|\craft\base\ElementInterface[]|null
     */
    public function getPrices($status = self::STATUS_ENABLED)
    {
        return StripePlugin::$app->prices->getPricesByProductId($this->id, $status);
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