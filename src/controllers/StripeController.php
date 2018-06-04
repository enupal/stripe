<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;
use enupal\stripe\Stripe as StripePlugin;
use yii\web\NotFoundHttpException;

class StripeController extends BaseController
{
    protected $allowAnonymous = ['save-order'];

    /**
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $order = StripePlugin::$app->orders->processPayment();

        if (is_null($order)){
            throw new NotFoundHttpException();
        }

        return $this->redirectToPostedUrl($order);
    }
}
