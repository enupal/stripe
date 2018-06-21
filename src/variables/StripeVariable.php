<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\variables;

use enupal\stripe\elements\Order;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\enums\FrequencyType;
use enupal\stripe\services\PaymentForms;
use enupal\stripe\Stripe;
use craft\helpers\Template as TemplateHelper;
use Craft;

/**
 * Stripe Payments provides an API for accessing information about stripe buttons. It is accessible from templates via `craft.enupalStripe`.
 *
 */
class StripeVariable
{
    /**
     * @var Order
     */
    public $orders;

    public function __construct()
    {
        $this->orders = Order::find();
    }

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
     * Returns a complete Payment Form for display in template
     *
     * @param string     $handle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function paymentForm($handle, array $options = null)
    {
        return Stripe::$app->paymentForms->getPaymentFormHtml($handle, $options);
    }

    /**
     * @return array
     */
    public function getCurrencyIsoOptions()
    {
        return Stripe::$app->paymentForms->getIsoCurrencies();
    }

    /**
     * @return array
     */
    public function getCurrencyOptions()
    {
        return Stripe::$app->paymentForms->getCurrencies();
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        return Stripe::$app->paymentForms->getLanguageOptions();
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        return Stripe::$app->paymentForms->getDiscountOptions();
    }

    /**
     * @return array
     */
    public function getAmountTypeOptions()
    {
        return Stripe::$app->paymentForms->getAmountTypeOptions();
    }

    /**
     * @return array
     */
    public function getOrderStatuses()
    {
        $options = [];
        $options[OrderStatus::NEW] = Stripe::t('New');
        $options[OrderStatus::PROCESSED] = Stripe::t('Processed');

        return $options;
    }

    /**
     * @return array
     */
    public function getFrequencyOptions()
    {
        $options = [];
        $options[FrequencyType::YEAR] = Stripe::t('Year');
        $options[FrequencyType::MONTH] = Stripe::t('Month');
        $options[FrequencyType::WEEK] = Stripe::t('Week');
        $options[FrequencyType::DAY] = Stripe::t('Day');

        return $options;
    }

    /**
     * @return array
     */
    public function getSubscriptionsTypes()
    {
        $options = Stripe::$app->paymentForms->getSubscriptionsTypes();

        return $options;
    }

    /**
     * @return array
     */
    public function getSubscriptionsPlans()
    {
        $options = Stripe::$app->paymentForms->getSubscriptionsTypes();

        return $options;
    }

    /**
     * @param $block mixed
     *
     * @return string
     * @throws \Exception
     */
    public function labelToHandle($block)
    {
        $label = $block->label ?? Stripe::$app->orders->getRandomStr();
        $handleFromUser = $block->fieldHandle ?? $label;

        $handle = Stripe::$app->paymentForms->labelToHandle($handleFromUser);

        return strtolower($handle);
    }


    /**
     * @param $block
     * @return \Twig_Markup
     * @throws \yii\base\Exception
     * @throws \yii\base\ExitException
     */
    public function displayField($block)
    {
        $templatePath = Stripe::$app->paymentForms->getEnupalStripePath();
        $view = Craft::$app->getView();
        $view->setTemplatesPath($templatePath);
        $preValue = '';

        if ($block->type == 'hidden'){
            if ($block->hiddenValue) {
                try {
                    $preValue = Craft::$app->view->renderObjectTemplate($block->hiddenValue, Stripe::$app->paymentForms->getFieldVariables());
                } catch (\Exception $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            }
        }

        $htmlField = $view->renderTemplate(
            '_layouts/'.strtolower($block->type), [
                'block' => $block,
                'preValue' => $preValue
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }

    /**
     * Display plans as dropdown or radio buttons to the user
     *
     * @param $type
     * @param $matrix
     *
     * @return \Twig_Markup
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayMultiSelect($type, $matrix)
    {
        $templatePath = Stripe::$app->paymentForms->getEnupalStripePath();
        $view = Craft::$app->getView();
        $view->setTemplatesPath($templatePath);

        $htmlField = $view->renderTemplate(
            '_multipleplans/'.strtolower($type), [
                'matrixField' => $matrix
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }

    /**
     * @param $planId
     * @return null|string
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public function getDefaultPlanName($planId)
    {
        $plan = Stripe::$app->plans->getStripePlan($planId);
        $planName = null;

        if ($plan){
            $planName = Stripe::$app->plans->getDefaultPlanName($plan);
        }

        return $planName;
    }

    /**
     * @param $number
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderByNumber($number)
    {
        $order = Stripe::$app->orders->getOrderByNumber($number);

        return $order;
    }

    /**
     * @param $id
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderById($id)
    {
        $order = Stripe::$app->orders->getOrderById($id);

        return $order;
    }

    /**
     * @return \enupal\stripe\elements\Order[]|null
     */
    public function getAllOrders()
    {
        $orders = Stripe::$app->orders->getAllOrders();

        return $orders;
    }

    /**
     * @param array $variables
     */
    public function addVariables(array $variables)
    {
        PaymentForms::addVariables($variables);
    }

    /**
     * @param $email
     * @return null|string
     */
    public function getCustomerReference($email)
    {
        $customerId = Stripe::$app->orders->getCustomerReference($email);

        return $customerId;
    }
}

