<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
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

        if (!Stripe::$app->settings->saveSettings($settings, $scenario)) {
            Craft::$app->getSession()->setError(Stripe::t('Couldn’t save settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Stripe::t('Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetSizeUrl()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $size = $request->getBodyParam('size');
        $language = $request->getBodyParam('language');

        $buttonUrl = Stripe::$app->buttons->getButtonSizeUrl($size, $language);

        return $this->asJson(['buttonUrl' => $buttonUrl]);
    }
}
