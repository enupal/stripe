<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\helpers\UrlHelper;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\AmountType;
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

        if ($publicData['enableSubscriptions']){
            $plan = $form->getSinglePlan();
            $params['subscription_data'] = [
                'items' => [[
                    'plan' => $plan['id'],
                    'quantity' => $publicData['quantity']
                ]],
                'metadata' => $metadata
            ];
        }else{
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
        }

        if ($form->amountType == AmountType::ONE_TIME_CUSTOM_AMOUNT && !$form->enableSubscriptions){
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
