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
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
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
     * @param $order
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function updateDescriptionToPaymentIntent(PaymentIntent $paymentIntent, $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();
        $description = Craft::$app->getView()->renderObjectTemplate($settings->chargeDescription, $order);
        PaymentIntent::update($paymentIntent->id, ['description' => $description]);
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
        $couponCode = $metadata['stripe_payments_coupon_code'];
        $amountBeforeCoupon = $metadata['stripe_payments_amount_before_coupon'];

        $charge = $paymentIntent['charges']['data'][0];
        $billing = $charge['billing_details'] ?? null;
        $shipping = $charge['shipping'] ?? null;

        $testMode = !$checkoutSession['livemode'];
        $customer = StripePlugin::$app->customers->getStripeCustomer($paymentIntent['customer']);
        StripePlugin::$app->customers->registerCustomer($customer, $testMode);
        $form = StripePlugin::$app->paymentForms->getPaymentFormById($formId);

        $data = [];
        $data['enupalStripe']['metadata'] = $this->removePaymentIntentMetadata($metadata);
        $data['enupalStripe']['token'] = $charge['id'];
        $data['enupalStripe']['email'] = $billing['email'];
        $data['enupalStripe']['formId'] = $formId;
        $data['enupalStripe']['amount'] = $paymentIntent['amount'];
        $data['enupalStripe']['quantity'] = $quantity;
        $data['enupalStripe']['testMode'] = $testMode;
        $data['enupalStripe']['paymentType'] = PaymentType::CC;
        $data['enupalStripe']['userId'] = $userId;

        $billingAddress = $billing['address'] ?? null;
        $shippingAddress = $shipping['address'] ?? null;

        if (isset($billingAddress['city']) && ($form->enableBillingAddress)){
            $data['enupalStripe']['billingAddress'] = [
                'country' => $billingAddress['country'],
                'zip' => $billingAddress['postal_code'],
                'line1' => $billingAddress['line1'],
                'city' => $billingAddress['city'],
                'state' => $billingAddress['state'],
                'name' => $billing['name'] ?? ''
            ];
        }

        if (isset($shippingAddress['city']) && ($form->enableShippingAddress)){
            $data['enupalStripe']['address'] = [
                'country' => $shippingAddress['country'],
                'zip' => $shippingAddress['postal_code'],
                'line1' => $shippingAddress['line1'],
                'city' => $shippingAddress['city'],
                'state' => $shippingAddress['state'],
                'name' => $shipping['name'] ?? ''
            ];
        }

        $order = StripePlugin::$app->orders->processPayment($data);
        if ($couponCode){
            $coupon = StripePlugin::$app->coupons->getCoupon($couponCode);
	        if ($coupon){
	            $couponAmount = StripePlugin::$app->orders->convertFromCents($amountBeforeCoupon, $order->currency) - $order->totalPrice;
                $order->couponCode = $coupon['id'];
                $order->couponName = $coupon['name'];
                $order->couponAmount = $couponAmount;
                $order->couponSnapshot = json_encode($coupon);
            }
	        StripePlugin::$app->orders->saveOrder($order);
        }

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
        $shippingAddress = $checkoutSession['shipping']['address'] ?? null;
        $customer = StripePlugin::$app->customers->getStripeCustomer($subscription['customer']);
        StripePlugin::$app->customers->registerCustomer($customer, $testMode);
        $form = StripePlugin::$app->paymentForms->getPaymentFormById($formId);

        $invoice = StripePlugin::$app->customers->getStripeInvoice($subscription['latest_invoice']);

        $amount = $subscription['plan']['amount'] * $quantity;
        if ($invoice){
            $amount = $invoice['amount_paid'] == 0 ? $amount: $invoice['amount_paid'];
        }

        $data = [];
        $data['enupalStripe']['metadata'] = $this->removePaymentIntentMetadata($metadata);
        $data['enupalStripe']['token'] = $subscription['id'];
        $data['enupalStripe']['email'] = $customer['email'];
        $data['enupalStripe']['formId'] = $formId;
        $data['enupalStripe']['amount'] = $amount;
        $data['enupalStripe']['quantity'] = $quantity;
        $data['enupalStripe']['testMode'] = $testMode;
        $data['enupalStripe']['paymentType'] = PaymentType::CC;
        $data['enupalStripe']['userId'] = $userId;

        if ($shippingAddress){
            $shippingAddress['name'] = $checkoutSession['shipping']['name'] ?? '';
            $data['enupalStripe']['address'] = $shippingAddress;
        }

        // For subscription the billing address is on payment method
        $paymentMethod = $this->getStripePaymentMethod($subscription['default_payment_method']);
        if ($paymentMethod) {
            $billing = $paymentMethod['billing_details'] ?? null;
            $billingAddress = $billing['address'] ?? null;

            if (isset($billingAddress['city']) && ($form->enableBillingAddress)){
                $data['enupalStripe']['billingAddress'] = [
                    'country' => $billingAddress['country'],
                    'zip' => $billingAddress['postal_code'],
                    'line1' => $billingAddress['line1'],
                    'city' => $billingAddress['city'],
                    'state' => $billingAddress['state'],
                    'name' => $billing['name'] ?? ''
                ];
            }
        }

        $order = StripePlugin::$app->orders->processPayment($data);

        return $order;
    }

    /**
     * @param $paymentMethodId
     * @return PaymentMethod|null
     * @throws \Exception
     */
    public function getStripePaymentMethod($paymentMethodId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $paymentMethod = null;
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        }catch (\Exception $e) {
            Craft::error('Unable to get payment method: '.$e->getMessage());
        }

        return $paymentMethod;
    }

    /**
     * @param $metadata
     * @return mixed
     */
    private function removePaymentIntentMetadata($metadata)
    {
        unset($metadata['stripe_payments_form_id']);
        unset($metadata['stripe_payments_user_id']);
        unset($metadata['stripe_payments_quantity']);
        unset($metadata['stripe_payments_coupon_code']);
        unset($metadata['stripe_payments_amount_before_coupon']);

        return $metadata;
    }
}
