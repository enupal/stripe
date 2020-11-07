<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\variables;

use enupal\stripe\elements\db\PaymentFormsQuery;
use enupal\stripe\elements\Order;
use enupal\stripe\elements\db\OrdersQuery;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\FrequencyType;
use enupal\stripe\services\PaymentForms;
use enupal\stripe\Stripe;
use craft\helpers\Template as TemplateHelper;
use DateTime;
use Craft;
use Psy\Util\Str;
use yii\base\Behavior;

/**
 * Stripe Payments provides an API for accessing information about stripe buttons. It is accessible from templates via `craft.enupalStripe`.
 *
 */
class StripeVariable extends Behavior
{
    /**
     * @var Order
     */
    public $orders;

    /**
     * @var PaymentForm
     */
    public $paymentForms;

    /**
     * Returns a new OrderQuery instance.
     *
     * @param mixed $criteria
     * @return OrdersQuery
     */
    public function orders($criteria = null): OrdersQuery
    {
        $query = Order::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    public function tax()
    {
        return Stripe::$app->taxes;
    }

    /**
     * Returns a new OrderQuery instance.
     *
     * @param mixed $criteria
     * @return PaymentFormsQuery
     */
    public function paymentForms($criteria = null): PaymentFormsQuery
    {
        $query = PaymentForm::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    /**
     *
     * @param $paymentFormId
     * @param $vendorId
     * @return PaymentForm|null
     */
    public function getVendorPaymentForm($paymentFormId,  $vendorId = null)
    {
        return Stripe::$app->paymentForms->getVendorPaymentForm((int)$paymentFormId, $vendorId);
    }

    public function getPaymentFormsByVendor($vendorId = null)
    {
        return Stripe::$app->paymentForms->getPaymentFormsByVendor($vendorId);
    }

    /**
     *
     * @param $productId
     * @param $vendorId
     * @return \craft\commerce\elements\Product|null
     */
    public function getVendorCommerceProduct($productId,  $vendorId = null)
    {
        return Stripe::$app->paymentForms->getVendorCommerceProduct((int)$productId, $vendorId);
    }

    public function getCommerceProductsByVendor($vendorId = null)
    {
        return Stripe::$app->paymentForms->getCommerceProductsByVendor($vendorId);
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
     * @return array|null
     */
    public function getConfigSettings()
    {
        return Stripe::$app->settings->getConfigSettings();
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        return Stripe::$app->settings->getPublishableKey();
    }

    /**
     * Returns a complete Payment Form for display in template
     *
     * @param string $handle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
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
    public function getAmountTypeOptions()
    {
        return Stripe::$app->paymentForms->getAmountTypeOptions();
    }

    /**
     * @return array
     */
    public function getOrderStatuses()
    {
        $statuses = Stripe::$app->orderStatuses->getAllOrderStatuses();
        $options = [];
        foreach ($statuses as $status) {
            $options[$status->id] = Stripe::t($status->name);
        }

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
     * @param $paymentForm PaymentForm
     * @param $block
     * @return \Twig_Markup
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function displayField($paymentForm, $block)
    {
        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);
        $preValue = '';

        $inputFilePath = $templatePaths['fields'] . DIRECTORY_SEPARATOR . strtolower($block->type);

        $this->setTemplateOverride($view, $inputFilePath, $templatePaths['fields']);

        if ($block->type == 'hidden') {
            if ($block->hiddenValue) {
                try {
                    $preValue = Craft::$app->view->renderObjectTemplate($block->hiddenValue, Stripe::$app->paymentForms->getFieldVariables());
                } catch (\Exception $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            }
        }

        $htmlField = $view->renderTemplate(
            strtolower($block->type), [
                'block' => $block,
                'preValue' => $preValue
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
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

        return $handle;
    }

    /**
     * Display plans as dropdown or radio buttons to the user
     *
     * @param $paymentForm PaymentForm
     *
     * @return \Twig_Markup
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function displayMultiSelect($paymentForm)
    {
        $type = $paymentForm->subscriptionStyle;
        $matrix = $paymentForm->enupalMultiplePlans;

        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);

        $inputFilePath = $templatePaths['multipleplans'] . DIRECTORY_SEPARATOR . strtolower($type);

        $this->setTemplateOverride($view, $inputFilePath, $templatePaths['multipleplans']);

        $htmlField = $view->renderTemplate(
            strtolower($type), [
                'matrixField' => $matrix
            ]
        );

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        return TemplateHelper::raw($htmlField);
    }

    /**
     * Display plans as dropdown or radio buttons to the user
     *
     * @param $paymentForm PaymentForm
     *
     * @param string $type
     * @return \Twig_Markup
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function displayAddress($paymentForm, $type = 'address')
    {
        $templatePaths = Stripe::$app->paymentForms->getFormTemplatePaths($paymentForm);
        $view = Craft::$app->getView();
        $defaultTemplate = Stripe::$app->paymentForms->getEnupalStripePath() . DIRECTORY_SEPARATOR . 'fields';
        $view->setTemplatesPath($defaultTemplate);

        $view->setTemplatesPath($templatePaths['address']);

        $htmlField = $view->renderTemplate(
            'address', [
                'paymentForm' => $paymentForm,
                'type' => $type
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

        if ($plan) {
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
     * @return  array
     */
    public function getVariables()
    {
        return Stripe::$app->paymentForms->getFieldVariables();
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

    /**
     * @param $paymentTypeOptions
     * @return array
     */
    public function getPaymentTypesAsOptions($paymentTypeOptions)
    {
        return Stripe::$app->paymentForms->getPaymentTypesAsOptions($paymentTypeOptions);
    }

    /**
     * @return array
     */
    public function getSubmitTypesAsOptions()
    {
        return Stripe::$app->checkout->getSubmitTypesAsOptions();
    }

    /**
     * @return array
     */
    public function getSofortCountriesAsOptions()
    {
        return Stripe::$app->paymentForms->getSofortCountriesAsOptions();
    }

    /**
     * @return array
     */
    public function getAllOrderStatuses()
    {
        return Stripe::$app->orderStatuses->getAllOrderStatuses();
    }

    /**
     * @return array
     */
    public function getAllSubscriptionGrants()
    {
        return Stripe::$app->subscriptions->getAllSubscriptionGrants();
    }

    /**
     * @param $orderId
     * @return array|\enupal\stripe\records\Message[]|null
     */
    public function getAllMessages($orderId)
    {
        return Stripe::$app->messages->getAllMessages($orderId);
    }

    /**
     * @return string
     * @throws \yii\db\Exception
     */
    public function getOrderCurrencies()
    {
        return Stripe::$app->orders->getOrderCurrencies();
    }

    /**
     * @param $handle
     * @return PaymentForm|null
     */
    public function getPaymentForm($handle)
    {
        return Stripe::$app->paymentForms->getPaymentFormBySku($handle);
    }

    /**
     * @param $settings
     * @return mixed
     */
    public function getPaymentFormsAsElementOptions($settings)
    {
        $variables['elementType'] = PaymentForm::class;
        $variables['paymentFormElements'] = null;

        if ($settings->syncDefaultFormId) {
            $paymentForms = $settings->syncDefaultFormId;
            if (is_string($paymentForms)) {
                $paymentForms = json_decode($settings->syncDefaultFormId);
            }

            $paymentFormElements = [];

            if (count($paymentForms)) {
                foreach ($paymentForms as $key => $paymentFormId) {
                    $paymentForm = Craft::$app->elements->getElementById($paymentFormId);
                    array_push($paymentFormElements, $paymentForm);
                }

                $variables['paymentFormElements'] = $paymentFormElements;
            }
        }

        return $variables;
    }

    /**
     * @return array
     */
    public function getSyncTypes()
    {
        $options = [
            1 => Craft::t('enupal-stripe', 'One-Time'),
            2 => Craft::t('enupal-stripe', 'Subscriptions')
        ];

        return $options;
    }

    /**
     * @return array
     */
    public function getOrderStatusesAsOptions()
    {
        $statuses = Stripe::$app->orderStatuses->getAllOrderStatuses();
        $statusArray = [];

        foreach ($statuses as $status) {
            $statusArray[$status['id']] = $status['name'];
        }

        return $statusArray;
    }

    /**
     * @param $string
     *
     * @return DateTime
     * @throws \Exception
     */
    public function getDate($string)
    {
        return new DateTime($string, new \DateTimeZone(Craft::$app->getTimeZone()));
    }

    /**
     * @param null $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByUser($userId = null)
    {
        if (is_null($userId)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $userId = $currentUser->id ?? null;
        }

        return Stripe::$app->subscriptions->getSubscriptionsByUser($userId);
    }

    /**
     * @param null $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getSubscriptionsByEmail($email = null)
    {
        if (is_null($email)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $email = $currentUser->email ?? null;
        }

        return Stripe::$app->subscriptions->getSubscriptionsByEmail($email);

    }

    /**
     * @param null $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByUser($userId = null)
    {
        if (is_null($userId)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $userId = $currentUser->id ?? null;
        }

        return Stripe::$app->orders->getSubscriptionsByUser($userId);
    }

    /**
     * @param null $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByEmail($email = null)
    {
        if (is_null($email)){
            $currentUser = Craft::$app->getUser()->getIdentity();
            $email = $currentUser->email ?? null;
        }

        return Stripe::$app->orders->getSubscriptionsByEmail($email);

    }

    /**
     * @return bool
     */
    public function getIsSnapshotInstalled()
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('enupal-snapshot');

        if (is_null($plugin)){
            return false;
        }

        return true;

    }

    /**
     * @return array
     */
    public function getAllCountriesAsList()
    {
        return Stripe::$app->countries->getAllCountriesAsList();
    }

    /**
     * @return \Stripe\Collection
     * @throws \Stripe\Error\Api
     */
    public function getAllCoupons()
    {
        return Stripe::$app->coupons->getAllCoupons();
    }

    /**
     * @param $amount
     * @param $currency
     * @return float|int
     */
    public function convertFromCents($amount, $currency)
    {
        return Stripe::$app->orders->convertFromCents($amount, $currency);
    }

    /**
     * @return int
     */
    public function getMode()
    {
        $settings = Stripe::$app->settings->getSettings();

        return $settings->testMode;
    }

    /**
     * @param $email
     * @return \Stripe\Customer|null
     * @throws \Exception
     */
    public function getStripeCustomer($email)
    {
        return Stripe::$app->customers->getStripeCustomerByEmail($email);
    }

    /**
     * @param $email
     * @param $successUrl
     * @param $cancelUrl
     * @return \Stripe\Checkout\Session|null
     * @throws \yii\base\Exception
     */
    public function getSetupSession($email, $successUrl, $cancelUrl)
    {
        return Stripe::$app->checkout->getSetupSession($email, $successUrl, $cancelUrl);
    }

    /**
     * @param $paymentMethodId
     * @return mixed
     */
    public function getPaymentMethod($paymentMethodId)
    {
        return Stripe::$app->customers->getPaymentMethod($paymentMethodId);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function getStripePlans()
    {
        $plans = Stripe::$app->plans->getStripePlans();

        return $plans;
    }

    /**
     * @return array
     */
    public function getConnectProductTypes()
    {
        return Stripe::$app->connects->getConnectProductTypesAsOptions();
    }

    public function getCurrentVendor()
    {
        return Stripe::$app->vendors->getCurrentVendor();
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return Stripe::$app->settings->getCallbackUrl();
    }

    /**
     * @return array
     */
    public function getBooleanUserFields()
    {
        return Stripe::$app->vendors->getBooleanUserFieldsAsOptions();
    }

    public function getCraftUserGroups()
    {
        return Craft::$app->getUserGroups()->getAllGroups();
    }

    public function isSuperVendor($vendorId)
    {
        return Stripe::$app->vendors->isSuperVendor($vendorId);
    }


    /**
     * @param $view
     * @param $inputFilePath
     * @param $templatePath
     */
    private function setTemplateOverride($view, $inputFilePath, $templatePath)
    {
        // Allow input field templates to be overridden
        foreach (Craft::$app->getConfig()->getGeneral()->defaultTemplateExtensions as $extension) {
            if (file_exists($inputFilePath . '.' . $extension)) {

                // Override Field Input template path
                $view->setTemplatesPath($templatePath);
                break;
            }
        }
    }
}

