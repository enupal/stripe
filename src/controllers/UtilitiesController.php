<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use Craft;
use enupal\stripe\elements\Vendor;
use enupal\stripe\services\Vendors;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use yii\base\InvalidArgumentException;

class UtilitiesController extends FrontEndController
{
    /**
     * @return \yii\web\Response
     * @throws \craft\errors\MissingComponentException
     */
    public function actionGetOauthLink()
    {
        $state = bin2hex(random_bytes('16'));
        $currentUrl = Craft::$app->getRequest()->referrer;
        Craft::$app->getSession()->set('enupal-current-url', $currentUrl);
        Craft::$app->getSession()->set('enupal-state', $state);
        $clientId = StripePlugin::$app->settings->getClientId();
        $currentUser = Craft::$app->getUser()->getIdentity();

        $params = array(
            'state' => $state,
            'client_id' => $clientId,
            'stripe_user[email]' => $currentUser->email
        );

        $url = 'https://connect.stripe.com/express/oauth/authorize?' . http_build_query($params);

        return $this->asJson(['url' => $url]);
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionAuthorizeOauth()
    {
        $request = Craft::$app->getRequest();
        $enupalState = $request->getRequiredQueryParam('state');
        $sessionState = Craft::$app->getSession()->get('enupal-state');
        $currentUrl = Craft::$app->getSession()->get('enupal-current-url');

        if ($enupalState !== $sessionState) {
            throw new \Exception('Incorrect state parameter', 403);
        }

        // Send the authorization code to Stripe's API.
        $code = $request->getRequiredQueryParam('code');
        $connectedAccountId = StripePlugin::$app->connects->getStripeUserIdFromCode($code);

        if ($connectedAccountId === null) {
            throw new \Exception('Unable to get the connect account id', 403);
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $vendor = StripePlugin::$app->vendors->getVendorByUserId($currentUser->id);

        if ($vendor === null) {
            $vendor = new Vendor();
            $vendor->userId = $currentUser->id;
            $settings = StripePlugin::$app->settings->getSettings();
            $vendor->vendorRate = $settings->globalRate;
            $vendor->paymentType = Vendors::PAYMENT_TYPE_ON_CHECKOUT;
            $vendor->testmode = $settings->testMode;
        }

        $vendor->enabled = true;
        $vendor->stripeId = $connectedAccountId;

        // Save it
        if (!Craft::$app->elements->saveElement($vendor)) {
            Craft::error('Unable to save vendor: '.json_encode($vendor->getErrors()));
        }

        return $this->redirect($currentUrl);
    }
}
