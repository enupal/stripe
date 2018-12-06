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
use enupal\stripe\jobs\SyncOneTimePayments;
use enupal\stripe\jobs\SyncSubscriptionPayments;
use enupal\stripe\models\Settings;
use enupal\stripe\Stripe;

class SettingsController extends BaseController
{
    /**
     * Save Plugin Settings
     *
     * @return \yii\web\Response
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = $request->getBodyParam('settings');
        $scenario = $request->getBodyParam('stripeScenario');
        $message = Stripe::t('Settings saved.');

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

        if ($scenario == 'sync'){
            $this->runSyncJob($settingsModel);
            $message = Stripe::t('Sync Orders added to the queue');
        }

        Craft::$app->getSession()->setNotice($message);

        return $this->redirectToPostedUrl();
    }

    /**
     * Updates all stripe plans as options for dropdown select field within matrix field
     *
     * @return \yii\web\Response
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionUpdatePlans()
    {
        $result = null;
        $this->requirePostRequest();

        try {
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

    /**
     * Sync payments from Stripe
     *
     * @param $settings Settings
     * @throws \yii\web\BadRequestHttpException
     */
    private function runSyncJob($settings)
    {
        $result = null;
        $this->requirePostRequest();
        $defaultSettings = [
            'totalSteps' => $settings->syncLimit,
            'defaultPaymentFormId' => $settings->syncDefaultFormId[0],
            'defaultStatusId' => $settings->syncDefaultStatusId,
            'syncIfUserExists' => $settings->syncIfUserExists
        ];
        if ($settings->syncType == 1){
            Craft::$app->queue->push(new SyncOneTimePayments($defaultSettings));
        }else{
            Craft::$app->queue->push(new SyncSubscriptionPayments($defaultSettings));
        }
    }
}
