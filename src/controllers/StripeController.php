<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\controllers;

use craft\web\Controller as BaseController;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\Stripe as StripePlugin;
use Craft;
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

        $enableCheckout = Craft::$app->getRequest()->getBodyParam('enableCheckout') ?? true;

        // Stripe Elements
        if (!$enableCheckout){
            $paymentType = Craft::$app->getRequest()->getBodyParam('paymentType');

            if ($paymentType == PaymentType::IDEAL){
                $response = StripePlugin::$app->orders->processIdealPayment();

                if (is_null($response) || !isset($response['source'])){
                    throw new NotFoundHttpException("Unable to process the IDeal Payment");
                }

                $source = $response['source'];

                return $this->redirect($source->redirect->url);
            }
        }

        // Stripe Checkout
        $order = StripePlugin::$app->orders->processPayment();

        if (is_null($order)){
            throw new NotFoundHttpException("Unable to process the Payment");
        }

        return $this->redirectToPostedUrl($order);
    }
}
