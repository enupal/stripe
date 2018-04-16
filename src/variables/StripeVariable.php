<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\variables;

use enupal\stripe\enums\OrderStatus;
use enupal\stripe\Stripe;
use enupal\stripe\PaypalButtons;
use Craft;

/**
 * EnupalStripe provides an API for accessing information about paypal buttons. It is accessible from templates via `craft.enupalPaypal`.
 *
 */
class StripeVariable
{
    /**
     * @return string
     */
    public function getName()
    {
        $plugin = Stripe::$app->settings->getPlugin();

        return $plugin->getName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $plugin = Stripe::$app->settings->getPlugin();

        return $plugin->getVersion();
    }

    /**
     * @return string
     */
    public function getSettings()
    {
        return Stripe::$app->settings->getSettings();
    }

    /**
     * Returns a complete PayPal Button for display in template
     *
     * @param string     $sku
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayButton($sku, array $options = null)
    {
        return Stripe::$app->buttons->getButtonHtml($sku, $options);
    }

    /**
     * @return array
     */
    public function getCurrencyIsoOptions()
    {
        return Stripe::$app->buttons->getIsoCurrencies();
    }

    /**
     * @return array
     */
    public function getCurrencyOptions()
    {
        return Stripe::$app->buttons->getCurrencies();
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        return Stripe::$app->buttons->getLanguageOptions();
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        return Stripe::$app->buttons->getDiscountOptions();
    }

    /**
     * @return array
     */
    public function getShippingOptions()
    {
        return Stripe::$app->buttons->getShippingOptions();
    }

    /**
     * @return array
     */
    public function getOrderStatuses()
    {
        $options = [];
        $options[OrderStatus::NEW] = Stripe::t('New');
        $options[OrderStatus::SHIPPED] = Stripe::t('Shipped');

        return $options;
    }
}

