<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use Stripe\PaymentIntent;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;

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

    public function createOrderFromPaymentIntent(PaymentIntent $intent, $formId)
    {
        // Recreate the data array and call order = StripePlugin::$app->orders->processPayment($postData);
        /*
        $data = [];
        $data['token']
        $data['email']
        $data['formId']
        $data['amount']
        $data['quantity']
        $data['testMode']
        $data['quantity']
        */
    }
}
