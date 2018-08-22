<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use craft\web\Controller as BaseController;
use enupal\stripe\Stripe;

class SettingsController extends BaseController
{
    /**
     * Save Plugin Settings
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = $request->getBodyParam('settings');
        $scenario = $request->getBodyParam('stripeScenario');

        $plugin = Stripe::$app->settings->getPlugin();
        $settingsModel = $plugin->getSettings();

        $settingsModel->setAttributes($settings, false);

        if (!Stripe::$app->settings->saveSettings($settingsModel, $scenario)) {

            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settingsModel
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Stripe::t('Settings saved.'));

        return $this->redirectToPostedUrl();
    }


    /**
     * Updates all stripe plans as options for dropdown select field within matrix field
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionUpdatePlans()
    {
        $result = null;

        try {
            $this->requirePostRequest();

            $result = Stripe::$app->plans->getUpdatePlans();

        } catch (\Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        if (!$result){
            Craft::$app->getSession()->setError(Stripe::t('No plans were found in stripe. Check your Stripe Keys'));
        }
        else{
            Craft::$app->getSession()->setNotice(Stripe::t('Stripe plans updated.'));
        }

        return $this->redirectToPostedUrl();
    }
}
