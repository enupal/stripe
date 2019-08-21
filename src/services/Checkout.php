<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\AmountType;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\models\CustomPlan;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Checkout\Session;
use yii\base\Component;

class Checkout extends Component
{
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
     * @param PaymentForm $form
     * @param $postData
     * @return Session
     * @throws \Exception
     */
    public function createCheckoutSession(PaymentForm $form, $postData)
    {
        $publicData = $postData['enupalStripeData'] ?? null;

        StripePlugin::$app->settings->initializeStripe();
        $askAddress = $publicData['enableShippingAddress'] || $publicData['enableBillingAddress'];
        $data = $publicData['stripe'];
        $metadata = [
            'stripe_payments_form_id' => $form->id,
            'stripe_payments_user_id' => Craft::$app->getUser()->getIdentity()->id ?? null,
            'stripe_payments_quantity' => $publicData['quantity']
        ];

        $metadata = array_merge($metadata, $postData['metadata'] ?? []);

        $params = [
            'payment_method_types' => ['card'],

            'success_url' => $this->getSiteUrl('enupal/stripe-payments/finish-order?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getSiteUrl($publicData['checkoutCancelUrl']),
        ];

        $isCustomAmount = isset($postData['recurringToggle']) && $postData['recurringToggle'] == 'on';

        if ($form->enableSubscriptions || $isCustomAmount){
            $params = $this->handleSubscription($form, $postData, $metadata, $params, $isCustomAmount);
        }else if (!$isCustomAmount){
            $params = $this->handleOneTimePayment($form, $postData, $metadata, $params);
        }

        if ($form->amountType == AmountType::ONE_TIME_CUSTOM_AMOUNT && !$form->enableSubscriptions && !$isCustomAmount){
            $params['submit_type'] = 'donate';
        }

        if ($askAddress){
            $params['billing_address_collection'] = 'required';
        }

        if ($data['email']){
            $params['customer_email'] = $data['email'];
        }

        $session = Session::create($params);

        return $session;
    }

    /**
     * @param $paymentForm
     * @param $postData
     * @param $metadata
     * @param $params
     * @param $isCustomAmount
     * @return null
     * @throws \Exception
     */
    private function handleSubscription(PaymentForm $paymentForm, $postData, $metadata, $params, $isCustomAmount = false)
    {
        $publicData = $postData['enupalStripeData'] ?? null;
        $data = $publicData['stripe'];
        // By default assume that is a single plan
        $plan = $paymentForm->getSinglePlan();
        $trialPeriodDays = null;

        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && $paymentForm->enableCustomPlanAmount) {
            if ($data['amount'] > 0){
                // test what is returning we need a stripe id
                $customPlan = new CustomPlan([
                    "amountInCents" => $data['amount'],
                    "interval" => $paymentForm->customPlanFrequency,
                    "intervalCount" => $paymentForm->customPlanInterval,
                    "currency" => $paymentForm->currency
                ]);

                if ($paymentForm->singlePlanTrialPeriod){
                    $trialPeriodDays = $paymentForm->singlePlanTrialPeriod;
                }

                $plan = StripePlugin::$app->plans->createCustomPlan($customPlan);
            }
        }

        if ($paymentForm->subscriptionType == SubscriptionType::MULTIPLE_PLANS) {
            $planId = $postData['enupalMultiPlan'] ?? null;

            if (is_null($planId) || empty($planId)){
                throw new \Exception(Craft::t('enupal-stripe','Plan Id is required'));
            }

            $plan = StripePlugin::$app->plans->getStripePlan($planId);
        }

        // Override plan if is a custom plan donation
        if ($isCustomAmount){
            $customPlan = new CustomPlan([
                "amountInCents" => $data['amount'],
                "interval" => $paymentForm->recurringPaymentType,
                "email" => $data['email'],
                "currency" => $paymentForm->currency
            ]);

            $plan = StripePlugin::$app->plans->createCustomPlan($customPlan);
        }

        $subscriptionData = [
            'items' => [[
                'plan' => $plan['id'],
                'quantity' => $publicData['quantity']
            ]],
            'metadata' => $metadata
        ];

        if ($trialPeriodDays){
            $subscriptionData['trial_period_days'] = $trialPeriodDays;
        }

        $params['subscription_data'] = $subscriptionData;

        return $params;
    }

    /**
     * @param $paymentForm
     * @param $postData
     * @param $metadata
     * @param $params
     * @return null
     * @throws \Exception
     */
    private function handleOneTimePayment(PaymentForm $paymentForm, $postData, $metadata, $params)
    {
        $publicData = $postData['enupalStripeData'] ?? null;
        $data = $publicData['stripe'];

        $lineItem = [
            'name' => $data['name'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'quantity' => $publicData['quantity'],
        ];

        if ($data['image']){
            $lineItem['images'] = [$data['image']];
        }

        $params['line_items'] = [$lineItem];

        $params['payment_intent_data'] = [
            'metadata' => $metadata
        ];

        return $params;
    }

    /**
     * @param $url
     *
     * @return string
     * @throws \yii\base\Exception
     */
    public function getSiteUrl($url)
    {
        if (UrlHelper::isAbsoluteUrl($url)){
            return $url;
        }

        return UrlHelper::siteUrl($url);
    }
}
