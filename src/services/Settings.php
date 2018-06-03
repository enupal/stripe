<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
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
     * @param string $scenario
     * @param SettingsModel  $settings
     *
     * @return bool
     */
    public function saveSettings(SettingsModel $settings, string $scenario = null): bool
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
        $pluginSettings =  (new Query())
            ->select(['settings'])
            ->from(['{{%plugins}}'])
            ->where(['handle' => 'enupal-stripe'])
            ->one();

        $settings = new SettingsModel();

        $settings->setAttributes(json_decode($pluginSettings['settings'], true), false);

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
}
