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
        $couponCode = $postData['enupalCouponCode'] ?? null;
        $metadata = [
            'stripe_payments_form_id' => $form->id,
            'stripe_payments_user_id' => Craft::$app->getUser()->getIdentity()->id ?? null,
            'stripe_payments_quantity' => $publicData['quantity'],
            'stripe_payments_coupon_code' => $couponCode,
            'stripe_payments_amount_before_coupon' => $data['amount']
        ];

        $metadata = array_merge($metadata, $postData['metadata'] ?? []);

        $sessionParams = [
            'payment_method_types' => ['card'],

            'success_url' => $this->getSiteUrl('enupal/stripe-payments/finish-order?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getSiteUrl($publicData['checkoutCancelUrl']),
        ];

        $isCustomAmount = isset($postData['recurringToggle']) && $postData['recurringToggle'] == 'on';

        if ($form->enableSubscriptions || $isCustomAmount){
            $sessionParams = $this->handleSubscription($form, $postData, $metadata, $sessionParams, $isCustomAmount);
        }else if (!$isCustomAmount){
            $sessionParams = $this->handleOneTimePayment($form, $postData, $metadata, $sessionParams);

	        $pluginSettings = StripePlugin::$app->settings->getSettings();

	        if (!$pluginSettings->capture){
		        $sessionParams['payment_intent_data']['capture_method'] = 'manual';
	        }
        }

        if ($form->amountType == AmountType::ONE_TIME_CUSTOM_AMOUNT && !$form->enableSubscriptions && !$isCustomAmount){
            $sessionParams['submit_type'] = 'donate';
        }

        if ($askAddress){
            $sessionParams['billing_address_collection'] = 'required';
        }

        if ($data['email']){
        	$customer = StripePlugin::$app->customers->getCustomerByEmail($data['email'], $publicData['testMode']);

	        $sessionParams['customer_email'] = $data['email'];
        	if ($customer !== null){
		        $stripeCustomer = StripePlugin::$app->customers->getStripeCustomer($customer->stripeId);
		        if ($stripeCustomer !== null){
			        $sessionParams['customer'] = $customer->stripeId;
			        unset($sessionParams['customer_email']);
		        }
	        }
        }

	    $sessionParams['locale'] = $form->language;

        $session = Session::create($sessionParams);

        return $session;
    }

    /**
     * @param $paymentForm
     * @param $postData
     * @param $metadata
     * @param $sessionParams
     * @param $isCustomAmount
     * @return null
     * @throws \Exception
     */
    private function handleSubscription(PaymentForm $paymentForm, $postData, $metadata, $sessionParams, $isCustomAmount = false)
    {
        $publicData = $postData['enupalStripeData'] ?? null;
        $data = $publicData['stripe'];
        // By default assume that is a single plan
        $plan = $paymentForm->getSinglePlan();
        $trialPeriodDays = null;
        $settings = Stripe::$app->settings->getSettings();
        $oneTineFee = [];

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
            $setupFee = StripePlugin::$app->orders->getSetupFeeFromMatrix($planId, $paymentForm);
            if ($setupFee && $setupFee > 0){
                $oneTineFee = [
                    'amount' =>  Stripe::$app->orders->convertToCents($setupFee, $paymentForm->currency),
                    'currency' => $paymentForm->currency,
                    'name' => $settings->oneTimeSetupFeeLabel,
                    'quantity' => 1
                ];
            }
        }

        // Override plan if is a custom plan donation
        if ($isCustomAmount){
            $customPlan = new CustomPlan([
                "amountInCents" => $data['amount'],
                "interval" => $paymentForm->recurringPaymentType,
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

        $sessionParams['subscription_data'] = $subscriptionData;

        // One time fees
        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN){
            if ($paymentForm->singlePlanSetupFee && $paymentForm->singlePlanSetupFee > 0){
                $oneTineFee = [
                    'amount' =>  Stripe::$app->orders->convertToCents($paymentForm->singlePlanSetupFee, $paymentForm->currency),
                    'currency' => $paymentForm->currency,
                    'name' => $settings->oneTimeSetupFeeLabel,
                    'quantity' => 1
                ];
            }
        }

        if ($oneTineFee){
            $sessionParams['line_items'] = [$oneTineFee];
        }

        return $sessionParams;
    }

    /**
     * @param $paymentForm
     * @param $postData
     * @param $metadata
     * @param $sessionParams
     * @return null
     * @throws \Exception
     */
    private function handleOneTimePayment(PaymentForm $paymentForm, $postData, $metadata, $sessionParams)
    {
        $publicData = $postData['enupalStripeData'] ?? null;
        $data = $publicData['stripe'];
        $couponCode = $postData['enupalCouponCode'] ?? null;

        if ($couponCode !== null){
            $couponRedeemed = StripePlugin::$app->coupons->applyCouponToAmountInCents($data['amount'], $couponCode, $paymentForm->currency, false);
            if ($couponRedeemed->isValid){
                $data['amount'] = $couponRedeemed->finalAmount;
            }
        }

        $lineItem = [
            'name' => $data['name'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'quantity' => $publicData['quantity'],
        ];

	    $logoAssets = $paymentForm->getLogoAssets();
	    $logoUrls = [];
	    if ($logoAssets){
		    foreach ($logoAssets as $logoAsset){
			    $logoUrls[] = $logoAsset->getUrl();
		    }
	    }

        if ($data['image']){
            $lineItem['images'] = $logoUrls;
        }

        $sessionParams['line_items'] = [$lineItem];

        $sessionParams['payment_intent_data'] = [
            'metadata' => $metadata
        ];

        return $sessionParams;
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
