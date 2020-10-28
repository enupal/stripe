<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @dedicado Al amor de vida, mi compañera de vida y motivacion de cualquier deseo ardiente de exito, a mi Sara **).
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe;

use Craft;
use craft\commerce\elements\Product;
use craft\elements\User;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\UserEvent;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Users;
use craft\web\UrlManager;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\events\AfterPopulatePaymentFormEvent;
use enupal\stripe\events\OrderCompleteEvent;
use enupal\stripe\events\WebhookEvent;
use enupal\stripe\services\App;
use enupal\stripe\services\Orders;
use craft\commerce\elements\Order as CommerceOrder;
use enupal\stripe\services\PaymentForms;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;
use enupal\stripe\fields\StripePaymentForms as StripePaymentFormsField;

use enupal\stripe\variables\StripeVariable;
use enupal\stripe\models\Settings;
use craft\base\Plugin;

class Stripe extends Plugin
{
    /**
     * Enable use of Stripe::$app-> in place of Craft::$app->
     *
     * @var App
     */
    public static $app;

    public $hasCpSection = true;
    public $hasCpSettings = true;
    public $schemaVersion = '3.0.0';

    public function init()
    {
        parent::init();

        self::$app = $this->get('app');

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getSiteUrlRules());
        }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('enupalStripe', StripeVariable::class);
                $variable->set('enupalstripe', StripeVariable::class);
            }
        );

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = StripePaymentFormsField::class;
        });

        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $event) {
            /** @var User $user */
            $element = $event->element;
            if (get_class($element) === User::class){
                self::$app->customers->updateCustomerEmail($element);
            }

            if (get_class($element) === PaymentForm::class){
                self::$app->vendors->assignPaymentFormToVendor($element);
            }

            if (get_class($element) === Product::class){
                self::$app->vendors->assignCommerceProductToVendor($element);
            }
        });

        Event::on(Orders::class, Orders::EVENT_AFTER_PROCESS_WEBHOOK, function(WebhookEvent $e) {
            self::$app->subscriptions->processSubscriptionGrantEvent($e);
        });

        Event::on(Orders::class, Orders::EVENT_AFTER_ORDER_COMPLETE, function(OrderCompleteEvent $e) {
            self::$app->commissions->processSeparateCharges($e->order);
        });

        if (self::$app->connects->isCommerceInstalled()) {
            Event::on(CommerceOrder::class, CommerceOrder::EVENT_AFTER_COMPLETE_ORDER, function(Event $e) {
                // @var CommerceOrder $order
                $order = $e->sender;
                self::$app->commissions->processCommerceSeparateCharges($order);
            });

            Event::on(Product::class, Product::EVENT_AFTER_VALIDATE, function(Event $e) {
                if (Craft::$app->getRequest()->getIsSiteRequest()) {
                    $product = $e->sender;
                    self::$app->paymentForms->handleCommerceProducts($product);
                }
            });
        }

        Event::on(PaymentForms::class, PaymentForms::EVENT_AFTER_POPULATE, function(AfterPopulatePaymentFormEvent $e) {
            if (Craft::$app->getRequest()->getIsSiteRequest()) {
                self::$app->paymentForms->handleVendorPaymentForms($e->paymentForm);
            }
        });

        Event::on(Users::class, Users::EVENT_AFTER_ACTIVATE_USER, function(UserEvent $e) {
            self::$app->vendors->processUserActivation($e->user);
        });

        Craft::$app->projectConfig
            ->onAdd("enupalStripe.fields.{uid}", [$this, 'handleChangedField'])
            ->onUpdate("enupalStripe.fields{uid}", [$this, 'handleChangedField'])
            ->onRemove("enupalStripe.fields.{uid}", [$this, 'handleDeletedField']);
    }

    public function handleChangedField(\craft\events\ConfigEvent $event)
    {
        $data = $event->newValue;
        $fieldUid = $event->tokenMatches[0];
        Craft::$app->fields->applyFieldSave($fieldUid, $data,  self::$app->settings->getFieldContext());
    }

    public function handleDeletedField(\craft\events\ConfigEvent $event)
    {
        $fieldUid = $event->tokenMatches[0];
        Craft::$app->fields->applyFieldDelete($fieldUid);
    }

    /**
     * @inheritdoc
     * @throws \Throwable
     */
    protected function afterInstall()
    {
        Stripe::$app->paymentForms->createDefaultVariantFields();
    }

    /**
     * @inheritdoc
     */
    protected function afterUninstall()
    {
        Stripe::$app->paymentForms->deleteVariantFields();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();
        $navs = [
            'subnav' => [
                'orders' => [
                    "label" => self::t("Orders"),
                    "url" => 'enupal-stripe/orders'
                ],
                'forms' => [
                    "label" => self::t("Payment Forms"),
                    "url" => 'enupal-stripe/forms'
                ]
            ]
        ];

        /** @var Settings $settings */
        $settings = $this->getSettings();

        if ($settings->enableConnect){
            $navs['subnav']['commissions'] = [
                "label" => self::t("Commissions"),
                "url" => 'enupal-stripe/commissions'
            ];
            $navs['subnav']['connects'] = [
                "label" => self::t("Connect"),
                "url" => 'enupal-stripe/connects'
            ];
            $navs['subnav']['vendors'] = [
                "label" => self::t("Vendors"),
                "url" => 'enupal-stripe/vendors'
            ];
        }

        if ($settings->useSca){
            $navs['subnav']['tax'] = [
                "label" => self::t("Tax"),
                "url" => 'enupal-stripe/tax'
            ];
        }

        $navs['subnav']['coupons'] = [
            "label" => self::t("Coupons"),
            "url" => 'enupal-stripe/coupons'
        ];

        $navs['subnav']['settings'] = [
            "label" => self::t("Settings"),
            "url" => 'enupal-stripe/settings'
        ];

        return array_merge($parent, $navs);
    }

    /**
     * @inheritdoc
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('enupal-stripe/settings/index');
    }

    /**
     * @param string $message
     * @param array  $params
     *
     * @return string
     */
    public static function t($message, array $params = [])
    {
        return Craft::t('enupal-stripe', $message, $params);
    }

    /**
     * @return array
     */
    private function getCpUrlRules()
    {
        return [
            'enupal-stripe/forms/new' =>
                'enupal-stripe/payment-forms/edit-form',

            'enupal-stripe/forms/edit/<formId:\d+>' =>
                'enupal-stripe/payment-forms/edit-form',

            'enupal-stripe/orders/edit/<orderId:\d+>' =>
                'enupal-stripe/orders/edit-order',

            'enupal-stripe/settings/order-statuses/new' =>
                'enupal-stripe/order-statuses/edit',

            'enupal-stripe/settings/subscription-grants/new' =>
                'enupal-stripe/subscription-grants/edit',
            'enupal-stripe/settings/subscription-grants/<subscriptionGrantId:\d+>' =>
                'enupal-stripe/subscription-grants/edit',

            'enupal-stripe/settings/order-statuses/<orderStatusId:\d+>' =>
                'enupal-stripe/order-statuses/edit',

            'enupal-stripe/vendors/new' =>
                'enupal-stripe/vendors/edit-vendor',

            'enupal-stripe/vendors/edit/<vendorId:\d+>' =>
                'enupal-stripe/vendors/edit-vendor',

            'enupal-stripe/connects/new' =>
                'enupal-stripe/connects/edit-connect',

            'enupal-stripe/connects/edit/<connectId:\d+>' =>
                'enupal-stripe/connects/edit-connect',

            'enupal-stripe/commissions/new' =>
                'enupal-stripe/commissions/edit-commission',

            'enupal-stripe/commissions/edit/<commissionId:\d+>' =>
                'enupal-stripe/commissions/edit-commission',
        ];
    }

    /**
     * @return array
     */
    private function getSiteUrlRules()
    {
        return [
            'enupal-stripe/update-billing-info' =>
                'enupal-stripe/stripe/update-billing-info',
            'enupal/stripe-payments' =>
                'enupal-stripe/webhook/stripe',
            'enupal/stripe-payments/finish-order' =>
                'enupal-stripe/stripe/finish-order',
            'enupal/validate-coupon' =>
                'enupal-stripe/coupons/validate',
            'enupal/stripe/create-checkout-session' =>
                'enupal-stripe/checkout/create-session',
            'enupal/stripe-payments/finish-setup-session' =>
                'enupal-stripe/stripe/finish-setup-session',
            'enupal-stripe/update-subscription' =>
                'enupal-stripe/stripe/update-subscription',
            'enupal-stripe/customer-portal' =>
                'enupal-stripe/stripe/create-customer-portal',
            'enupal-stripe/get-oauth-link' =>
                'enupal-stripe/utilities/get-oauth-link',
            'enupal-stripe/authorize-oauth' =>
                'enupal-stripe/utilities/authorize-oauth',
        ];
    }
}

