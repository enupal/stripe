<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\fields;

use craft\fields\BaseRelationField;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\Stripe as StripePlugin;

/**
 * Class StripePaymentForms
 *
 */
class StripePaymentForms extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public $allowMultipleSources = false;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return StripePlugin::t('Stripe Payment Forms');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return PaymentForm::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return StripePlugin::t('Add a Stripe Payment Form');
    }
}
