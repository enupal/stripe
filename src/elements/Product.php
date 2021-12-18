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
    public $id;
    public $stripeId;
    public $active;
    public $description;
    public $metadata;
    public $name;
    public $created;
    public $images;
    public $packageDimensions;
    public $shippable;
    public $statementDescriptor;
    public $taxCode;
    public $unitLabel;
    public $url;

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
    public static function refHandle()
    {
        return 'product';
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
            'enupal-stripe/products/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        $name = $this->name ?? $this->stripeId;

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

        // Delete @todo update to disabled products on stipe
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => StripePlugin::t('Are you sure you want to disable the selected products?'),
            'successMessage' => StripePlugin::t('Products disabled.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['stripeId', 'name'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'created' => StripePlugin::t('Created at')
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

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['name', 'stripeId'];

        return $attributes;
    }

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
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
            $record = ProductRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Connect ID: '.$this->id);
            }
        } else {
            $record = new ProductRecord();
            $record->id = $this->id;
        }

        $record->vendorId = $this->vendorId;
        $record->products = $this->products;
        $record->productType = $this->productType;
        $record->allProducts = $this->allProducts;
        $record->rate = $this->rate;
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [];
        $rules[] = [['vendorId', 'productType', 'rate'], 'required'];

        $rules[] = [
            ['products'], 'required', 'when' => function($model) {
                return $model->allProducts != 1;
            }
        ];

        return $rules;
    }

    /**
     * @return Vendor|null
     */
    public function getVendor()
    {
        if ($this->vendorId){
            return StripePlugin::$app->vendors->getVendorById($this->vendorId);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getProductTypeName()
    {
        return $this->isCommerceType() ? 'Commerce' : 'Stripe Payments';
    }

    /**
     * @return bool
     */
    public function isCommerceType()
    {
        return strpos($this->productType, 'commerce') !== false;
    }

    /**
     * @return string
     */
    public function getAllProductsHtml()
    {
        $statuses = [
            'enabled' => 'green',
            'disabled' => 'white'
        ];

        $status = $this->allProducts ? 'enabled' : 'disabled';
        $color = $statuses[$status] ?? '';

        $html = "<span class='status ".$color."'> </span>";

        return $html;
    }
}