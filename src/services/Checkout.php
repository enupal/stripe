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
use enupal\stripe\elements\Cart;
use enupal\stripe\elements\PaymentForm;
use enupal\stripe\enums\SubmitTypes;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\events\CheckoutEvent;
use enupal\stripe\models\CustomPlan;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Checkout\Session;
use Stripe\SetupIntent;
use yii\base\Component;

class Checkout extends Component
{
    const USAGE_TYPE_METERED = 'metered';
    const SESSION_MODE_SUBSCRIPTION = 'subscription';
    const SESSION_MODE_PAYMENT = 'payment';
    const EVENT_BEFORE_CREATE_SESSION = 'beforeCreateSession';
    const METADATA_CHECKOUT_TWIG = 'stripe_payments_checkout_twig';
    const METADATA_CART_NUMBER = 'stripe_payments_cart_number';

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
            Craft::error('Unable to get checkout session: ' . $e->getMessage(), __METHOD__);
        }

        return $checkoutSession;
    }

    /**
     * @param array $lineItems
     * @param array $metadata
     * @return string|Session
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \yii\base\Exception
     */
    public function checkout(array $lineItems, array $metadata = [])
    {
        if (!StripePlugin::getInstance()->is(StripePlugin::EDITION_PRO)) {
            return "This feature is only available on Stripe Payments Pro";
        }

        $metadata[self::METADATA_CHECKOUT_TWIG] = true;
        $session = $this->createCheckoutSessionPro($lineItems, $metadata);

        return $session;
    }

    /**
     * @param Cart $cart
     * @return Session|null
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \yii\base\Exception
     */
    public function createCartCheckoutSession(Cart $cart)
    {
        if (!StripePlugin::getInstance()->is(StripePlugin::EDITION_PRO)) {
            return null;
        }

        $metadata = [
            self::METADATA_CART_NUMBER => $cart->number
        ];

        $metadata = array_merge($metadata, $cart->getCartMetadata());
        unset($metadata[self::METADATA_CHECKOUT_TWIG]);

        $lineItems = $this->fixCartItems($cart->getItems());

        return $this->createCheckoutSessionPro($lineItems, $metadata);
    }

    public function getAllCheckoutItems(string $checkoutId)
    {
        StripePlugin::$app->settings->initializeStripe();

        $startingAfter = null;
        $items = Session::allLineItems($checkoutId, ['limit' => 50, 'starting_after' => $startingAfter]);
        $cartItems = [];

        while(isset($items['data']) && is_array($items['data'])) {
            foreach ($items['data'] as $item) {
                $cartItems[] = $item;
            }

            $startingAfter = $item['id'];
            if ($items['has_more']){
                $items = Session::allLineItems($checkoutId, ['limit' => 50, 'starting_after' => $startingAfter]);
            }else{
                $items = null;
            }
        }

        return $cartItems;
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
        $pluginSettings = StripePlugin::$app->settings->getSettings();

        StripePlugin::$app->settings->initializeStripe();
        $askShippingAddress = $publicData['enableShippingAddress'] ?? false;
        $askBillingAddress = $publicData['enableBillingAddress'] ?? false;
        $data = $publicData['stripe'];
        $couponCode = $postData['enupalCouponCode'] ?? null;
        $user = Craft::$app->getUser()->getIdentity();
        $metadata = [
            'stripe_payments_form_id' => $form->id,
            'stripe_payments_user_id' => $user->id ?? null,
            'stripe_payments_quantity' => $publicData['quantity'],
            'stripe_payments_coupon_code' => $couponCode,
            'stripe_payments_amount_before_coupon' => $data['amount']
        ];

        $metadata = array_merge($metadata, $postData['metadata'] ?? []);
        $paymentMethods = json_decode($form->checkoutPaymentType, true);
        $paymentMethods = $paymentMethods ?? ['card'];

        $sessionParams = [
            'payment_method_types' => $paymentMethods,

            'success_url' => $this->getSiteUrl('enupal/stripe-payments/finish-order?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getSiteUrl($publicData['checkoutCancelUrl']),
        ];

        $isCustomAmount = isset($postData['recurringToggle']) && $postData['recurringToggle'] == 'on';

        if ($form->enableSubscriptions || $isCustomAmount) {
            $sessionParams = $this->handleSubscription($form, $postData, $metadata, $sessionParams, $isCustomAmount);
        } else if (!$isCustomAmount) {
            $sessionParams = $this->handleOneTimePayment($form, $postData, $metadata, $sessionParams);

            if (!$pluginSettings->capture) {
                $sessionParams['payment_intent_data']['capture_method'] = 'manual';
            }
        }

        if (!$form->enableSubscriptions && !$isCustomAmount) {
            $sessionParams['submit_type'] = $form->checkoutSubmitType;
        }

        if ($askBillingAddress) {
            $sessionParams['billing_address_collection'] = 'required';
        }

        if ($askShippingAddress) {
            $sessionParams['shipping_address_collection'] = [
                'allowed_countries' => $this->getShippingCountries()
            ];
        }

        if ($data['email']) {
            $stripeCustomer = null;
            $customer = StripePlugin::$app->customers->getCustomerByEmail($data['email'], $publicData['testMode']);
            if ($customer !== null) {
                $stripeCustomer = StripePlugin::$app->customers->getStripeCustomer($customer->stripeId);
            }

            if (is_null($stripeCustomer) && !is_null($user)) {
                $stripeCustomer = StripePlugin::$app->customers->getStripeCustomerByUser($user);
            }

            $sessionParams['customer_email'] = $data['email'];

            if ($stripeCustomer !== null) {
                $sessionParams['customer'] = $stripeCustomer->id;
                unset($sessionParams['customer_email']);
            }
        }

        $sessionParams['locale'] = $form->language;

        $customLineItems = $postData['enupalLineItems'] ?? null;
        $removeDefaultItem = $postData['enupalRemoveDefaultItem'] ?? false;

        if ($customLineItems){
            $customLineItemsArray = json_decode($customLineItems, true);

            if ($customLineItemsArray){
                if ($removeDefaultItem) {
                    $sessionParams['line_items'] = [];
                }
                $sessionParams['line_items'] = array_merge($sessionParams['line_items'], $customLineItemsArray);
            }
        }

        // Adds support to allowPromotionCodes
        $allowPromotionCodes = (bool)$form->checkoutAllowPromotionCodes;

        if (isset($postData['enupalAllowPromotionCodes'])) {
            $allowPromotionCodesIsEnabled = filter_var($postData['enupalAllowPromotionCodes'], FILTER_VALIDATE_BOOLEAN);
            if ($allowPromotionCodesIsEnabled) {
                $allowPromotionCodes = true;
            }
        }

        if ($allowPromotionCodes) {
            if ($form->enableSubscriptions || $isCustomAmount) {
                $sessionParams['subscription_data']['payment_behavior'] = 'allow_incomplete';
            }

            $sessionParams['allow_promotion_codes'] = true;
        }

        if ($form->automaticTax) {
            $sessionParams['automatic_tax'] = [
                'enabled' => true
            ];

            if (isset($sessionParams['customer'])) {
                $sessionParams['customer_update'] = [
                    'shipping' => 'auto'
                ];
            }
        }

        $event = new CheckoutEvent([
            'sessionParams' => $sessionParams,
            'isCart' => false
        ]);

        $this->trigger(self::EVENT_BEFORE_CREATE_SESSION, $event);

        $session = Session::create($event->sessionParams);

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
        $oneTimeFee = [];

        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && $paymentForm->enableCustomPlanAmount) {
            if ($data['amount'] > 0) {
                // test what is returning we need a stripe id
                $customPlan = new CustomPlan([
                    "amountInCents" => $data['amount'],
                    "interval" =>  $postData['customFrequency'] ?? $paymentForm->customPlanFrequency,
                    "intervalCount" => $postData['customInterval'] ?? $paymentForm->customPlanInterval,
                    "currency" => $paymentForm->currency
                ]);

                $finalTrialPeriodDays = $postData['customTrialPeriodDays'] ?? $paymentForm->singlePlanTrialPeriod;

                if ($finalTrialPeriodDays) {
                    $trialPeriodDays = $finalTrialPeriodDays;
                }

                $plan = StripePlugin::$app->plans->createCustomPlan($customPlan, $paymentForm);
            }
        }

        if ($paymentForm->subscriptionType == SubscriptionType::MULTIPLE_PLANS) {
            $planId = $postData['enupalMultiPlan'] ?? null;

            if (is_null($planId) || empty($planId)) {
                throw new \Exception(Craft::t('enupal-stripe', 'Plan Id is required'));
            }

            $plan = StripePlugin::$app->plans->getStripePlan($planId);
            $setupFee = StripePlugin::$app->orders->getSetupFeeFromMatrix($planId, $paymentForm);
            if ($setupFee && $setupFee > 0) {
                $oneTimeFee = [
                    'amount' => Stripe::$app->orders->convertToCents($setupFee, $paymentForm->currency),
                    'currency' => $paymentForm->currency,
                    'name' => $settings->oneTimeSetupFeeLabel,
                    'quantity' => 1
                ];
            }
        }

        // Override plan if is a custom plan donation
        if ($isCustomAmount) {
            $customPlan = new CustomPlan([
                "amountInCents" => $data['amount'],
                "interval" => $paymentForm->recurringPaymentType,
                "currency" => $paymentForm->currency
            ]);

            $plan = StripePlugin::$app->plans->createCustomPlan($customPlan, $paymentForm);
        }

        $planItem =  [
            'plan' => $plan['id'],
            'quantity' => $publicData['quantity']
        ];

        if ($plan['usage_type'] === self::USAGE_TYPE_METERED) {
            unset($planItem['quantity']);
        }

        $subscriptionData = [
            'items' => [
                $planItem
            ],
            'metadata' => $metadata
        ];

        $trialPeriodDays = $postData['enupalSinglePlanTrialDays'] ?? $trialPeriodDays;

        if ($trialPeriodDays) {
            $subscriptionData['trial_period_days'] = $trialPeriodDays;
        }

        $subscriptionData = $this->processTaxCheckoutSession($paymentForm, $subscriptionData, true);

        $sessionParams['subscription_data'] = $subscriptionData;

        // One time fees
        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN) {
            if ($paymentForm->singlePlanSetupFee && $paymentForm->singlePlanSetupFee > 0) {
                $oneTimeFee = [
                    'amount' => Stripe::$app->orders->convertToCents($paymentForm->singlePlanSetupFee, $paymentForm->currency),
                    'currency' => $paymentForm->currency,
                    'name' => $settings->oneTimeSetupFeeLabel,
                    'quantity' => 1
                ];
            }
        }

        if ($oneTimeFee) {
            $sessionParams['line_items'] = [$oneTimeFee];
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
        $checkoutImages = isset($postData['enupalCheckoutImages']) ? json_decode($postData['enupalCheckoutImages']) : null;

        if ($couponCode) {
            $couponRedeemed = StripePlugin::$app->coupons->applyCouponToAmountInCents($data['amount'], $couponCode, $paymentForm->currency, false);
            if ($couponRedeemed->isValid) {
                $data['amount'] = $couponRedeemed->finalAmount;
            }
        }

        if ($paymentForm->adjustableQuantity) {
            $publicData['quantity'] = $paymentForm->adjustableQuantityMin > $publicData['quantity'] ? (int)$paymentForm->adjustableQuantityMin : $publicData['quantity'];
        }

        $lineItem = [
            'name' => $data['name'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'quantity' => $publicData['quantity']
        ];

        if ($paymentForm->adjustableQuantity) {
            $maxQuantity = (int)$paymentForm->adjustableQuantityMax;
            if (!$paymentForm->hasUnlimitedStock) {
                $maxQuantity = $paymentForm->adjustableQuantityMax > $paymentForm->quantity ? $paymentForm->quantity : (int)$paymentForm->adjustableQuantityMax;
            }

            $minQuantity = $paymentForm->adjustableQuantityMin <= 0 ? 1 : (int)$paymentForm->adjustableQuantityMin;
            $lineItem['adjustable_quantity'] = ['enabled' => true, 'minimum' => $minQuantity, 'maximum' => $maxQuantity];
        }

        $logoAssets = $paymentForm->getLogoAssets();
        $logoUrls = [];
        if ($logoAssets) {
            foreach ($logoAssets as $logoAsset) {
                $logoUrls[] = $logoAsset->getUrl();
            }
        }

        if ($data['image']) {
            $lineItem['images'] = $logoUrls;
        }

        if (!is_null($checkoutImages) && is_array($checkoutImages)) {
            $lineItem['images'] = $checkoutImages;
        }

        $lineItem = $this->processTaxCheckoutSession($paymentForm, $lineItem);

        $sessionParams['line_items'] = [$lineItem];

        $metadata = StripePlugin::$app->orders->getStripeMetadata([
            'metadata' => $metadata
        ]);

        $sessionParams['payment_intent_data']['metadata'] = $metadata;

        $sessionParams['mode'] = 'payment';

        return $sessionParams;
    }

    /**
     * @param PaymentForm $paymentForm
     * @param $lineItem
     * @param bool $isRecurring
     * @return mixed
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function processTaxCheckoutSession(PaymentForm $paymentForm, $lineItem, $isRecurring = false)
    {
        if (empty($paymentForm->tax)) {
            return $lineItem;
        }

        $tax = json_decode($paymentForm->tax, true);

        if (is_array($tax) && count($tax)) {
            $taxKey = $paymentForm->useDynamicTaxRate ? Taxes::DYNAMIC_TAX_RATES : Taxes::TAX_RATES;

            if ($isRecurring) {
                $taxKey = Taxes::DEFAULT_TAX_RATES;
            }

            $lineItem[$taxKey] = $tax;

            if ($taxKey === Taxes::DYNAMIC_TAX_RATES) {
                $lineItem = $this->processDynamicTaxes($lineItem, $paymentForm);
            }
        }

        return $lineItem;
    }

    /**
     * @param array $lineItem
     * @param PaymentForm $paymentForm
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function processDynamicTaxes(array $lineItem, PaymentForm $paymentForm)
    {
        $price = StripePlugin::$app->prices->createPrice($lineItem);

        if (!is_null($price)) {
            unset($lineItem['amount']);
            unset($lineItem['currency']);
            unset($lineItem['name']);
            unset($lineItem['description']);
            unset($lineItem['images']);
            $lineItem['price'] = $price['id'];
        }

        return $lineItem;
    }

    /**
     * @param $url
     *
     * @return string
     * @throws \yii\base\Exception
     */
    public function getSiteUrl($url)
    {
        if (UrlHelper::isAbsoluteUrl($url)) {
            return $url;
        }

        return UrlHelper::siteUrl($url);
    }

    /**
     * @return array
     */
    public function getSubmitTypesAsOptions()
    {
        $submitTypes = [
            [
                'label' => 'Auto',
                'value' => SubmitTypes::AUTO
            ],
            [
                'label' => 'Pay',
                'value' => SubmitTypes::PAY
            ],
            [
                'label' => 'Donate',
                'value' => SubmitTypes::DONATE
            ],
            [
                'label' => 'Book',
                'value' => SubmitTypes::BOOK
            ],
        ];

        return $submitTypes;
    }

    /**
     * @param $email
     * @param $successUrl
     * @param $cancelUrl
     * @return Session|null
     * @throws \yii\base\Exception
     */
    public function getSetupSession($email, $successUrl, $cancelUrl)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripeCustomer = StripePlugin::$app->customers->getStripeCustomerByEmail($email);

        if (is_null($stripeCustomer)){
            return null;
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'setup',
            'customer_email' => $email,
            'setup_intent_data' => [
                'metadata' => [
                    'customer_id' => $stripeCustomer->id,
                    'success_url' => $successUrl
                ],
            ],
            'success_url' => $this->getSiteUrl('enupal/stripe-payments/finish-setup-session?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getSiteUrl($cancelUrl)
        ]);

        return $session;
    }

    /**
     * @param $setupIntentId
     * @return SetupIntent|null
     * @throws \Exception
     */
    public function getSetupIntent($setupIntentId)
    {
        StripePlugin::$app->settings->initializeStripe();

        try{
            $setupIntent = SetupIntent::retrieve($setupIntentId);
        }catch (\Exception $e){
            Craft::error($e->getMessage(), __METHOD__);
            return null;
        }

        return $setupIntent;
    }

    public function getShippingCountries()
    {
        return ["AC", "AD", "AE", "AF", "AG", "AI", "AL", "AM", "AO", "AQ", "AR", "AT", "AU", "AW", "AX", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BL", "BM", "BN", "BO", "BQ", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CV", "CW", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN", "IO", "IQ", "IS", "IT", "JE", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "ME", "MF", "MG", "MK", "ML", "MM", "MN", "MO", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA", "SB", "SC", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "SS", "ST", "SV", "SX", "SZ", "TA", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VN", "VU", "WF", "WS", "XK", "YE", "YT", "ZA", "ZM", "ZW", "ZZ"];
    }

    /**
     * When a cart is updated with 0 is removed but the index need to be in order on stripe
     * @param array $items
     * @return array
     */
    private function fixCartItems(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $lineItems
     * @param array $metadata
     * @return Session
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \yii\base\Exception
     */
    private function createCheckoutSessionPro(array $lineItems, array $metadata): Session
    {
        StripePlugin::$app->settings->initializeStripe();
        $pluginSettings = StripePlugin::$app->settings->getSettings();
        Craft::$app->getSession()->set(PaymentForm::SESSION_CHECKOUT_SUCCESS_URL, $pluginSettings->cartSuccessUrl);

        $paymentMethods = $pluginSettings->cartPaymentMethods ?? ['card'];
        $user = Craft::$app->getUser()->getIdentity() ?? null;
        $metadata['stripe_payments_user_id'] = $user->id ?? null;

        $mode = $this->getIsSubscription($lineItems) ?
            self::SESSION_MODE_SUBSCRIPTION :
            self::SESSION_MODE_PAYMENT;

        $sessionParams = [
            'payment_method_types' => $paymentMethods,
            'locale' => $pluginSettings->cartLanguage,
            'line_items' => $lineItems,
            'success_url' => $this->getSiteUrl('enupal/stripe-payments/finish-order?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->getSiteUrl($pluginSettings->cartCancelUrl),
            'metadata' => $metadata,
            'mode' => $mode
        ];

        if (!$pluginSettings->capture) {
            $sessionParams['payment_intent_data']['capture_method'] = 'manual';
        }

        if ($pluginSettings->cartEnableBillingAddress) {
            $sessionParams['billing_address_collection'] = 'required';
        }

        if ($pluginSettings->cartEnableShippingAddress) {
            $sessionParams['shipping_address_collection'] = [
                'allowed_countries' => $this->getShippingCountries()
            ];
        }

        if (!is_null($user)) {
            $stripeCustomer = null;
            $customer = StripePlugin::$app->customers->getCustomerByEmail($user->email, $pluginSettings->testMode);
            if ($customer !== null) {
                $stripeCustomer = StripePlugin::$app->customers->getStripeCustomer($customer->stripeId);
            }

            if (is_null($stripeCustomer)) {
                $stripeCustomer = StripePlugin::$app->customers->getStripeCustomerByUser($user);
            }

            $sessionParams['customer_email'] = $user->email;

            if ($stripeCustomer !== null) {
                $sessionParams['customer'] = $stripeCustomer->id;
                unset($sessionParams['customer_email']);
            }
        }

        $allowPromotionCodes = $pluginSettings->cartAllowPromotionCodes;
        if ($allowPromotionCodes) {
            if ($mode == self::SESSION_MODE_SUBSCRIPTION) {
                $sessionParams['subscription_data']['payment_behavior'] = 'allow_incomplete';
            }

            $sessionParams['allow_promotion_codes'] = true;
        }

        // Shipping behavior, only for one-time payments
        if (!empty($pluginSettings->cartShippingRates) && $mode == self::SESSION_MODE_PAYMENT) {
            $shippingRates = [];
            foreach ($pluginSettings->cartShippingRates as $cartShippingRate) {
                $stripeShippingRate = StripePlugin::$app->shipping->getShippingRate($cartShippingRate);
                if (!isset($stripeShippingRate['active']) || !$stripeShippingRate['active']) {
                    Craft::warning("Skipped Shipping Rate on Cart Checkout: " . $cartShippingRate);
                    continue;
                }

                $shippingRate = [
                    'shipping_rate' => $cartShippingRate
                ];
                $shippingRates[] = $shippingRate;
            }

            if (!empty($shippingRates)) {
                $sessionParams['shipping_options'] = $shippingRates;
            }
        }

        // We go in favor of automatic tax, otherwise developers may need update each line_items using BeforeCreateCheckoutSession event
        // to add tax_rates workflow. More info -> https://stripe.com/docs/billing/taxes/collect-taxes?tax-calculation=tax-rates
        if ($pluginSettings->cartAutomaticTax) {
            $sessionParams['automatic_tax'] = [
                'enabled' => true
            ];

            if (isset($sessionParams['customer'])) {
                $sessionParams['customer_update'] = [
                    'shipping' => 'auto'
                ];
            }
        }

        $event = new CheckoutEvent([
            'sessionParams' => $sessionParams,
            'isCart' => true
        ]);

        $this->trigger(self::EVENT_BEFORE_CREATE_SESSION, $event);

        $session = Session::create($event->sessionParams);

        return $session;
    }

    /**
     * If at least 1 item in the cart is subscription return true
     * @param array $lineItems
     * @return bool
     */
    private function getIsSubscription(array $lineItems): bool
    {
        foreach ($lineItems as $item) {
            if (isset($item['price_data']['recurring'])) {
                return true;
            }
        }

        foreach ($lineItems as $item) {
            if (!isset($item['price'])) {
                continue;
            }
            $price = StripePlugin::$app->prices->getPriceByStripeId($item['price']);

            if ($price->getStripeObject()->type == Prices::PRICE_TYPE_RECURRING) {
                return true;
            }
        }

        return false;
    }
}
