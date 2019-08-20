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
use enupal\stripe\records\Customer as CustomerRecord;
use enupal\stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\StripeObject;
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

        $charge = $paymentIntent['charges']['data'][0];
        $billing = $charge['billing_details'];

        $testMode = !$checkoutSession['livemode'];
        $customer = Stripe::$app->customers->getStripeCustomer($paymentIntent['customer']);
        Stripe::$app->customers->registerCustomer($customer, $testMode);

        $data = [];
        $data['enupalStripe']['token'] = $charge['id'];
        $data['enupalStripe']['email'] = $billing['email'];
        $data['enupalStripe']['formId'] = $formId;
        $data['enupalStripe']['amount'] = $paymentIntent['amount'];
        $data['enupalStripe']['quantity'] = $quantity;
        $data['enupalStripe']['testMode'] = $testMode;
        $data['enupalStripe']['paymentType'] = PaymentType::CC;
        $data['enupalStripe']['userId'] = $userId;

        $address = $billing['address'] ?? null;

        if (isset($address['city'])){
            $data['enupalStripe']['billingAddress'] = [
                'country' => $address['country'],
                'zip' => $address['postal_code'],
                'line1' => $address['line1'],
                'city' => $address['city'],
                'state' => $address['state'],
                'name' => $billing['name']
            ];

            $data['enupalStripe']['sameAddressToggle'] = 'on';
        }

        $order = StripePlugin::$app->orders->processPayment($data);

        return $order;
    }


    /**
     * @param $subscription
     * @param $checkoutSession
     * @return \enupal\stripe\elements\Order|null
     * @throws \Throwable
     */
    public function createOrderFromSubscription($subscription, $checkoutSession)
    {
        $metadata = $subscription['metadata'];
        $formId = $metadata['stripe_payments_form_id'];
        $userId = $metadata['stripe_payments_user_id'];
        $quantity = $metadata['stripe_payments_quantity'];
        $testMode = !$checkoutSession['livemode'];
        $customer = Stripe::$app->customers->getStripeCustomer($subscription['customer']);
        Stripe::$app->customers->registerCustomer($customer, $testMode);

        $invoice = Stripe::$app->customers->getStripeInvoice($subscription['latest_invoice']);

        $amount = $subscription['plan']['amount'] * $quantity;
        if ($invoice){
            $amount = $invoice['amount_paid'];
        }

        $data = [];
        $data['enupalStripe']['token'] = $subscription['id'];
        $data['enupalStripe']['email'] = $customer['email'];
        $data['enupalStripe']['formId'] = $formId;
        $data['enupalStripe']['amount'] = $amount;
        $data['enupalStripe']['quantity'] = $quantity;
        $data['enupalStripe']['testMode'] = $testMode;
        $data['enupalStripe']['paymentType'] = PaymentType::CC;
        $data['enupalStripe']['userId'] = $userId;

        $order = StripePlugin::$app->orders->processPayment($data);

        return $order;
    }

}
