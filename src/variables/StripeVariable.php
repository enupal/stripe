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
use craft\helpers\Template as TemplateHelper;
use Craft;

/**
 * EnupalStripe provides an API for accessing information about stripe buttons. It is accessible from templates via `craft.enupalStripe`.
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
     * @param string     $handle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayButton($handle, array $options = null)
    {
        return Stripe::$app->buttons->getButtonHtml($handle, $options);
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

    /**
     * @param $label
     *
     * @return string
     */
    public function labelToHandle($label)
    {
        $handle = Stripe::$app->buttons->labelToHandle($label);

        return strtolower($handle);
    }


    /**
     * @param $block
     *
     * @return \Twig_Markup
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayField($block)
    {
        $templatePath = Stripe::$app->buttons->getEnupalStripePath();
        $view = Craft::$app->getView();
        $view->setTemplatesPath($templatePath);

        $htmlField = $view->renderTemplate(
            '_layouts/'.$block->type, [
                'block' => $block
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }
}

