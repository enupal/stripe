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
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;
use enupal\stripe\elements\db\VendorsQuery;
use enupal\stripe\records\Vendor as VendorRecord;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\Stripe;

/**
 * Vendor represents a entry element.
 */
class Vendor extends Element
{
    // General - Properties
    // =========================================================================
    public $id;
    public $userId;
    public $stripeId;
    public $paymentType;
    public $skipAdminReview;
    public $vendorRate;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Vendors');
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
            'enupal-stripe/vendors/edit/'.$this->id
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
        return $this->getVendorName();
    }

    /**
     * @inheritdoc
     *
     * @return VendorsQuery The newly created [[VendorsQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new VendorsQuery(get_called_class());
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
                'label' => StripePlugin::t('All Vendors'),
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
            'confirmationMessage' => StripePlugin::t('Are you sure you want to delete the selected vendors?'),
            'successMessage' => StripePlugin::t('Vendors deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['userId', 'stripeId', 'paymentType'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'vendorRate' => StripePlugin::t('Rate')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['userId'] = ['label' => StripePlugin::t('User')];
        $attributes['stripeId'] = ['label' => StripePlugin::t('Stripe Id')];
        $attributes['paymentType'] = ['label' => StripePlugin::t('Payment Type')];
        $attributes['vendorRate'] = ['label' => StripePlugin::t('Vendor Rate')];
        $attributes['skipAdminReview'] = ['label' => StripePlugin::t('Skip Admin Review')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['userId', 'stripeId', 'paymentType', 'vendorRate', 'skipAdminReview'];

        return $attributes;
    }

    /**
     * @inheritdoc
     * @param string $attribute
     * @return string
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'stripeId':
            {
                return empty($this->stripeId) ? '-' : $this->stripeId;
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
        // Get the Vendor record
        if (!$isNew) {
            $record = VendorRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid Vendor ID: '.$this->id);
            }
        } else {
            $record = new VendorRecord();
            $record->id = $this->id;
        }

        $record->userId = $this->userId;
        $record->stripeId = $this->stripeId;
        $record->paymentType = $this->paymentType;
        $record->skipAdminReview = $this->skipAdminReview;
        $record->vendorRate = $this->vendorRate;
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userId'], 'required'],
            [['vendorRate'], 'number', 'min'=> '1', 'max'=>'100', 'numberPattern' => '/^\d+(.\d{1,2})?$/'],
        ];
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        if ($this->userId) {
            return Craft::$app->getUsers()->getUserById($this->userId);
        }

        return null;
    }

    /**
     * @return string
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function getVendorName()
    {
        $vendorName = '';
        $user = $this->getUser();
        $settings = StripePlugin::$app->settings->getSettings();

        if ($user !== null){
            $format = empty($settings->vendorNameFormat) ? '{username}' : $settings->vendorNameFormat;
            $vendorName = Craft::$app->getView()->renderObjectTemplate($format, $user);
        }

        return $vendorName;
    }
}