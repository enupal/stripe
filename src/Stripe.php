<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fields;
use craft\web\UrlManager;
use enupal\stripe\events\OrderCompleteEvent;
use enupal\stripe\services\App;
use enupal\stripe\services\Orders;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;
use enupal\stripe\fields\Buttons as BuyNowButtonField;

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
    public $schemaVersion = '1.0.0';

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
                $variable->set('stripeButton', StripeVariable::class);
            }
        );

        Event::on(Orders::class, Orders::EVENT_AFTER_ORDER_COMPLETE, function(OrderCompleteEvent $e) {
            Stripe::$app->orders->sendCustomerNotification($e->order);
            Stripe::$app->orders->sendAdminNotification($e->order);
        });

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = BuyNowButtonField::class;
        });
    }

    /**
     * @inheritdoc
     */
    protected function afterInstall()
    {
        Stripe::$app->buttons->createDefaultVariantFields();
    }

    /**
     * @inheritdoc
     */
    protected function afterUninstall()
    {
        Stripe::$app->buttons->deleteVariantFields();
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
                'buttons' => [
                    "label" => self::t("Buttons"),
                    "url" => 'enupal-stripe/buttons'
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
            'enupal-stripe/buttons/new' =>
                'enupal-stripe/buttons/edit-button',

            'enupal-stripe/buttons/edit/<buttonId:\d+>' =>
                'enupal-stripe/buttons/edit-button',

            'enupal-stripe/orders/edit/<orderId:\d+>' =>
                'enupal-stripe/orders/edit-order',

            'enupal-stripe/payments/new' =>
                'enupal-stripe/payments/edit-button',

            'enupal-stripe/payments/edit/<paymentId:\d+>' =>
                'enupal-stripe/payments/edit-button',
        ];
    }

    /**
     * @return array
     */
    private function getSiteUrlRules()
    {
        return [
            'enupal-stripe/ipn' =>
                'enupal-stripe/paypal/ipn'
        ];
    }
}

