<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;
use enupal\stripe\Stripe as StripePlugin;

class StripeController extends BaseController
{

    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $result = StripePlugin::$app->orders->processPayment();

        return $this->redirectToPostedUrl();
    }
}
