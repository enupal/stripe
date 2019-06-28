<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\enums\PaymentType;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use yii\web\NotFoundHttpException;

class PaymentIntents extends Component
{
    /**
     * @param $id
     * @return PaymentIntent|null
     */
    public function getPaymentIntent($id)
    {
        $paymentIntent = null;

        try {
            StripePlugin::$app->settings->initializeStripe();

            $paymentIntent = PaymentIntent::retrieve($id);
        } catch (\Exception $e) {
            Craft::error('Unable to get payment intent: '.$e->getMessage(), __METHOD__);
        }

        return $paymentIntent;
    }

    /**
     * @param $sessionId
     * @return Session|null
     */
    public function getCheckoutSession($sessionId)
    {
        $checkoutSession = null;

        try {
            StripePlugin::$app->settings->initializeStripe();
            $checkoutSession = Session::retrieve($sessionId);
        } catch (\Exception $e) {
            Craft::error('Unable to get checkout session: '.$e->getMessage(), __METHOD__);
        }

        return $checkoutSession;
    }

    /**
     * @param PaymentIntent $paymentIntent
     * @param $checkoutSession
     * @return \enupal\stripe\elements\Order|null
     * @throws \Throwable
     */
    public function createOrderFromPaymentIntent(PaymentIntent $paymentIntent, $checkoutSession)
    {
        $metadata = $paymentIntent['metadata'];
        $formId = $metadata['stripe_payments_form_id'];
        $userId = $metadata['stripe_payments_user_id'];
        $quantity = $metadata['stripe_payments_quantity'];

        // Recreate the data array and call order = StripePlugin::$app->orders->processPayment($postData);
        $charge = $paymentIntent['charges']['data'][0];
        $billing = $charge['billing_details'];

        $data = [];
        $data['enupalStripe']['token'] = $charge['id'];
        $data['enupalStripe']['email'] = $billing['email'];
        $data['enupalStripe']['formId'] = $formId;
        $data['enupalStripe']['amount'] = $paymentIntent['amount'];
        $data['enupalStripe']['quantity'] = $quantity;
        $data['enupalStripe']['testMode'] = !$checkoutSession['livemode'];
        $data['enupalStripe']['paymentType'] = PaymentType::CC;
        $data['enupalStripe']['userId'] = $userId;

        $order = StripePlugin::$app->orders->processPayment($data);

        return $order;
    }
}
