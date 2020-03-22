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
use craft\elements\User;
use craft\events\ElementEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\UrlManager;
use enupal\stripe\events\WebhookEvent;
use enupal\stripe\services\App;
use enupal\stripe\services\Orders;
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
    public $schemaVersion = '2.5.0';

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
            $user = $event->element;
            if (get_class($user) === User::class){
                self::$app->customers->updateCustomerEmail($user);
            }
        });

        Event::on(Orders::class, Orders::EVENT_AFTER_PROCESS_WEBHOOK, function(WebhookEvent $e) {
            self::$app->subscriptions->processSubscriptionGrantEvent($e);
        });
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
        return array_merge($parent, [
            'subnav' => [
                'orders' => [
                    "label" => self::t("Orders"),
                    "url" => 'enupal-stripe/orders'
                ],
                'forms' => [
                    "label" => self::t("Payment Forms"),
                    "url" => 'enupal-stripe/forms'
                ],
                'coupons' => [
                    "label" => self::t("Coupons"),
                    "url" => 'enupal-stripe/coupons'
                ],
                'settings' => [
                    "label" => self::t("Settings"),
                    "url" => 'enupal-stripe/settings'
                ]
            ]
        ]);
    }

    /**
     * @inheritdoc
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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
        ];
    }
}

