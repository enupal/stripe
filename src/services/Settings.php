<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\db\Query;
use yii\base\Component;
use enupal\stripe\models\Settings as SettingsModel;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Stripe;

class Settings extends Component
{
    /**
     * Saves Settings
     *
     * @param $scenario
     * @param $settings SettingsModel
     *
     * @return bool
     */
    public function saveSettings(SettingsModel $settings, $scenario = null): bool
    {
        $plugin = $this->getPlugin();

        if (!is_null($scenario)) {
            $settings->setScenario($scenario);
        }

        // Validate them, now that it's a model
        if ($settings->validate() === false) {
            return false;
        }

        $success = Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->getAttributes());

        return $success;
    }

    /**
     * @return SettingsModel
     */
    public function getSettings()
    {
        $settings = $this->getPlugin()->getSettings();

        $configSettings = $this->getConfigSettings();
        // Overrides config settings
        $settings->livePublishableKey = $configSettings['livePublishableKey'] ?? $settings->livePublishableKey;
        $settings->liveSecretKey = $configSettings['liveSecretKey'] ?? $settings->liveSecretKey;
        $settings->testSecretKey = $configSettings['testSecretKey'] ?? $settings->testSecretKey;
        $settings->testPublishableKey = $configSettings['testPublishableKey'] ?? $settings->testPublishableKey;
        $settings->testMode = $configSettings['testMode'] ?? $settings->testMode;

        return $settings;
    }

    /**
     * @return \craft\base\PluginInterface|null
     */
    public function getPlugin()
    {
        return Craft::$app->getPlugins()->getPlugin('enupal-stripe');
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        $settings = $this->getSettings();
        $publishableKey = $settings->testMode ? $settings->testPublishableKey : $settings->livePublishableKey;

        return $publishableKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        $settings = $this->getSettings();

        $secretKey = $settings->testMode ? $settings->testSecretKey : $settings->liveSecretKey;

        return $secretKey;
    }

    /**
     * @throws \Exception
     */
    public function initializeStripe()
    {
        $privateKey = $this->getPrivateKey();

        if ($privateKey) {
            Stripe::setAppInfo(StripePlugin::getInstance()->name, StripePlugin::getInstance()->version, StripePlugin::getInstance()->documentationUrl);
            Stripe::setApiKey($privateKey);
        }
        else{
            throw new \Exception(Craft::t('enupal-stripe','Unable to get the stripe keys.'));
        }
    }

    /**
     * @return array|null
     */
    public function getConfigSettings()
    {
        return Craft::$app->config->getGeneral()->stripePayments ?? null;
    }
}
