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
use enupal\stripe\elements\db\ConnectQuery;
use enupal\stripe\records\Connect as ConnectRecord;
use enupal\stripe\Stripe as StripePlugin;
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
        return StripePlugin::t('Connect');
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
            'enupal-stripe/connects/edit/'.$this->id
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
        $name = $this->id;

        if ($this->vendorId) {
            $name = $this->getVendor()->getVendorName();
        }
        return (string)$name;
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
                'label' => StripePlugin::t('All Connects'),
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
            'confirmationMessage' => StripePlugin::t('Are you sure you want to delete the selected connects?'),
            'successMessage' => StripePlugin::t('Connects deleted.'),
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
            'rate' => StripePlugin::t('Rate')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['vendorId'] = ['label' => StripePlugin::t('Vendor')];
        $attributes['productType'] = ['label' => StripePlugin::t('Product Type')];
        $attributes['products'] = ['label' => StripePlugin::t('Products')];
        $attributes['rate'] = ['label' => StripePlugin::t('Rate')];
        $attributes['allProducts'] = ['label' => StripePlugin::t('All Products')];
        $attributes['dateCreated'] = ['label' => StripePlugin::t('Date created')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['vendorId', 'productType', 'rate', 'products', 'allProducts'];

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
            case 'productType':
            {
                return $this->productType::displayName();
            }
            case 'products':
            {
                if ($this->allProducts) {
                    return '(all)';
                }

                $products = is_string($this->products) ? json_decode($this->products, true) : $this->products;
                $products = empty($products) ? [] : $products;
                return count($products);
            }
            case 'allProducts':
            {
                return $this->getAllProductsHtml();
            }
            case 'rate':
            {
                return $this->rate.'%';
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