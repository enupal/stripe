<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use craft\db\Query;
use craft\helpers\Db;

use craft\helpers\Json;
use craft\helpers\UrlHelper;
use enupal\stripe\elements\Order;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\events\OrderCaptureEvent;
use enupal\stripe\events\OrderCompleteEvent;
use enupal\stripe\events\OrderRefundEvent;
use enupal\stripe\events\WebhookEvent;
use enupal\stripe\models\Address;
use enupal\stripe\models\CustomPlan;
use enupal\stripe\Stripe;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\Base;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Refund;
use Stripe\Source;
use yii\base\Component;
use enupal\stripe\records\Order as OrderRecord;
use enupal\stripe\records\Customer as CustomerRecord;

class Orders extends Component
{
    /**
     * @event OrderCompleteEvent The event that is triggered after a payment is made
     *
     * Plugins can get notified after a payment is made
     *
     * ```php
     * use enupal\stripe\events\OrderCompleteEvent;
     * use enupal\stripe\services\Orders;
     * use yii\base\Event;
     *
     * Event::on(Orders::class, Orders::EVENT_AFTER_ORDER_COMPLETE, function(OrderCompleteEvent $e) {
     *      $order = $e->order;
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_ORDER_COMPLETE = 'afterOrderComplete';

    /**
     * @event WebhookEvent The event that is triggered before a notification is send
     *
     * Plugins can get notified after process the webhook from Stripe
     *
     * ```php
     * use enupal\stripe\events\WebhookEvent;
     * use enupal\stripe\services\Orders;
     * use yii\base\Event;
     *
     * Event::on(Orders::class, Orders::EVENT_AFTER_PROCESS_WEBHOOK, function(WebhookEvent $e) {
     *      // https://stripe.com/docs/api#event_types
     *      $data = $e->stripeData;
     *      $order = $e->order;
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_PROCESS_WEBHOOK = 'afterProcessWebhook';

    /**
     * @event OrderRefundEvent The event that is triggered after a order is refunded
     *
     * Plugins can get notified after a order is refunded in the Control panel
     *
     * ```php
     * use enupal\stripe\events\OrderRefundEvent;
     * use enupal\stripe\services\Orders;
     * use yii\base\Event;
     *
     * Event::on(Orders::class, Orders::EVENT_AFTER_REFUND_ORDER, function(OrderRefundEvent $e) {
     *      $order = $e->order;
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_REFUND_ORDER = 'afterRefundOrder';

    const EVENT_AFTER_CAPTURE_ORDER = 'afterCaptureOrder';

    /**
     * Returns a Order model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|Order
     */
    public function getOrderById(int $id, int $siteId = null)
    {
        /** @var Order $order */
        $order = Craft::$app->getElements()->getElementById($id, Order::class, $siteId);

        return $order;
    }

    /**
     * Returns a Order model if one is found in the database by number
     *
     * @param string $number
     * @param int    $siteId
     *
     * @return null|Order
     */
    public function getOrderByNumber($number, int $siteId = null)
    {
        $query = Order::find();
        $query->number($number);
        $query->siteId($siteId);
        /** @var Order $order */
        $order = $query->one();

        return $order;
    }

    /**
     * Returns a Order model if one is found in the database by stripe transaction id
     *
     * @param string $stripeTransactionId
     * @param int    $siteId
     *
     * @return null|Order
     */
    public function getOrderByStripeId($stripeTransactionId, int $siteId = null)
    {
        if ($stripeTransactionId === null || $stripeTransactionId == ''){
            return null;
        }

        $query = Order::find();
        $query->stripeTransactionId($stripeTransactionId);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Return the currency Orders as Distinct strings
     * @return false|string
     * @throws \yii\db\Exception
     */
    public function getOrderCurrencies()
    {
        $tableName = Craft::$app->db->quoteTableName('{{%enupalstripe_orders}}');
        $sql = 'SELECT DISTINCT(currency) currency from '.$tableName;
        $results = Craft::$app->db->createCommand($sql)->queryAll();

        return $results ? json_encode($results) : '';
    }

    /**
     * Returns all orders
     *
     * @return null|Order[]
     */
    public function getAllOrders()
    {
        $query = Order::find();

        return $query->all();
    }

    /**
     * @param $order Order
     * @param $triggerEvent boolean
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveOrder(Order $order, $triggerEvent = true)
    {
        if ($order->id) {
            $orderRecord = OrderRecord::findOne($order->id);

            if (!$orderRecord) {
                throw new \Exception(StripePlugin::t('No Order exists with the ID “{id}”', ['id' => $order->id]));
            }
        }

        if (!$order->validate()) {
            return false;
        }

        try {
            $transaction = Craft::$app->db->beginTransaction();
            $result = Craft::$app->elements->saveElement($order);

            if ($result) {
                $transaction->commit();

                if ($order->isCompleted && !Craft::$app->getRequest()->getIsCpRequest() && $triggerEvent){
                    $event = new OrderCompleteEvent([
                        'order' => $order
                    ]);

                    $this->trigger(self::EVENT_AFTER_ORDER_COMPLETE, $event);
                    Stripe::$app->emails->sendNotificationEmails($order);
                }
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * @param Order $order
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteOrder(Order $order)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the Order Element
            $success = Craft::$app->elements->deleteElementById($order->id);

            if (!$success) {
                $transaction->rollback();
                Craft::error("Couldn’t delete Stripe Order", __METHOD__);

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int    $length   How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     *
     * @return string
     * @throws \Exception
     */
    public function getRandomStr($length = 12, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * @param Order $order
     *
     * @return Order
     */
    public function populatePaymentFormFromPost(Order $order)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $order->setAttributes($postFields, false);

        return $order;
    }

    /**
     * @param $data array
     * @param $isPending bool
     *
     * @return Order
     * @throws \Exception
     */
    public function populateOrder($data, $isPending = false)
    {
        $currentUserId = Craft::$app->getUser()->getIdentity()->id ?? null;
        $order = new Order();
        $order->orderStatusId = StripePlugin::$app->orderStatuses->getDefaultOrderStatusId();
        $order->isCompleted = $isPending ? false : true;
        $order->number = $this->getRandomStr();
        $order->userId = $data['userId'] ?? $currentUserId;
        $order->email = $data['email'];
        // The amount come in cents, we revert this just before save the order
        $order->totalPrice = $data['amount'];
        $order->quantity = $data['quantity'] ?? 1;
        $order->shipping = $data['shippingAmount'] ?? 0;
        $order->tax = $data['taxAmount'] ?? 0;
        $shippingAddress = $data['address'] ?? null;
        $billingAddress = $data['billingAddress'] ?? null;
        $sameAddress = $data['sameAddressToggle'] ?? null;

        if ($billingAddress){
            $order->billingAddressId = $this->getAddressId($billingAddress);
            if ($sameAddress === 'on'){
                $order->shippingAddressId = $order->billingAddressId;
            }
        }

        if ($shippingAddress){
            if (is_null($order->shippingAddressId)){
                $order->shippingAddressId = $this->getAddressId($shippingAddress);
            }
        }

        $order->testMode = $data['testMode'];
        // Variants
        $variants = $data['metadata'] ?? [];
        if ($variants){
            $order->variants = json_encode($variants);
        }

        return $order;
    }

    /**
     * Process Asynchronous Payment
     * iDEAL or SOFORT
     * For asynchronous payment methods, it can take up to several days to confirm whether the payment has been successful.
     * The status of the payment’s Charge object is initially set to pending,
     * until the payment has been confirmed as successful or failed via webhook
     *
     * @return array|null
     * @throws \Throwable
     * @throws \craft\errors\SiteNotFoundException
     */
    public function processAsynchronousPayment()
    {
        $result = null;
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('enupalStripe');
        $address = $request->getBodyParam('address') ?? null;
        $email = $request->getBodyParam('stripeElementEmail') ?? null;
        $formId = $data['formId'] ?? null;
        $data['email'] = $email;
        $data['couponCode'] = $request->getBodyParam('enupalCouponCode') ?? null;
        $paymentType = $request->getBodyParam('paymentType');
        $paymentOptions = Stripe::$app->paymentForms->getAsynchronousPaymentTypes();

        if ($address){
            $data['address'] = $address;
        }

        if (empty($email) || empty($formId) || !isset($paymentOptions[$paymentType])){
            Craft::error('Unable to get the formId, paymentType or email', __METHOD__);
            return $result;
        }

        $amount = $data['amount'] ?? null;
        if (empty($amount) || $amount == 'NaN'){
            Craft::error('Unable to get the final amount from the post request', __METHOD__);
            return $result;
        }

        $paymentForm = StripePlugin::$app->paymentForms->getPaymentFormById((int)$formId);

        if (is_null($paymentForm)) {
            throw new \Exception(Craft::t('enupal-stripe','Unable to find the Stripe Button associated to the order'));
        }

        $postData = $this->getPostData();
        $postData['enupalStripe']['email'] = $email;

        $order = $this->populateOrder($data, true);
        $order->paymentType = $request->getBodyParam('paymentType');
        $postData['currency'] = 'EUR';
        $postData['enupalCouponCode'] = $data['couponCode'];
        $order->postData = json_encode($postData);
        $order->currency = 'EUR';
        $order->formId = $paymentForm->id;

        $redirectUrl = $paymentForm->returnUrl != null ? UrlHelper::siteUrl($paymentForm->returnUrl) : StripePlugin::$app->settings->getPrimarySiteUrl();

        StripePlugin::$app->settings->initializeStripe();

        if (isset($data['couponCode']) && $data['couponCode']){
            $isSubscription = $paymentForm->enableSubscriptions ?? false;
            $this->applyCouponToOrder($data, $order, $isSubscription);
        }

        $options = [
            'type' => strtolower($paymentOptions[$paymentType]),
            'amount' => $order->totalPrice,
            'currency' => 'eur',
            'owner' => ['email' => $email],
            'metadata' => $this->getStripeMetadata($data)
        ];

        if (isset($postData['idealBank']) && $postData['idealBank'] && $order->paymentType == PaymentType::IDEAL){
            $options['ideal']['bank'] = $postData['idealBank'];
        }

        if (isset($postData['sofortCountry']) && $postData['sofortCountry'] && $order->paymentType == PaymentType::SOFORT){
            $options['sofort']['country'] = $postData['sofortCountry'];
        }

        // Save the order to get the order number
        if (!StripePlugin::$app->orders->saveOrder($order, false)){
            Craft::error('Something went wrong saving the Stripe Order: '.json_encode($order->getErrors()), __METHOD__);
            return $result;
        }

        $redirectUrl = Craft::$app->getView()->renderObjectTemplate($redirectUrl, $order);

        $options['redirect'] = ['return_url' => $redirectUrl];
        $source = Source::create($options);

        $order->stripeTransactionId = $source->id;
        // revert cents
        $order->totalPrice = $this->convertFromCents($order->totalPrice, $order->currency);

        // Finally save the order in Craft CMS
        if (!StripePlugin::$app->orders->saveOrder($order)){
            Craft::error('Something went wrong saving the Stripe Order: '.json_encode($order->getErrors()), __METHOD__);
            return $result;
        }

        $result = [
            'order' => $order,
            'source' => $source
        ];

        return $result;
    }

    /**
     * @param $order Order
     * @param $sourceObject
     * @param $type
     * @return Order|null
     * @throws \Throwable
     */
    public function asynchronousCharge($order, $sourceObject, $type)
    {
        $token = $order->stripeTransactionId;
        StripePlugin::$app->settings->initializeStripe();
        $paymentForm = $order->getPaymentForm();
        $postData = json_decode($order->postData, true);
        $data = $postData['enupalStripe'];

        if ($paymentForm->enableSubscriptions || (isset($data['recurringToggle']) && $data['recurringToggle'] == 'on')) {
            // Override source token with SEPA source token for recurring payments
            $token = $this->getSepaSource($token, $sourceObject, $type);
            // Let's mark this as complete if no error comes up from the sepa transaction
            // Users can check more info via webhooks
            $order->isCompleted = true;
        }

        $postData['enupalStripe']['token'] = $token;

        $order = $this->processPayment($postData, $order);

        return $order;
    }

    /**
     * Process Stripe Payment Listener
     *
     * @param $postData array
     * @param $order Order
     * @return Order|null
     * @throws \Exception
     * @throws \Throwable
     */
    public function processPayment($postData, $order = null)
    {
        $result = null;
        $data = $postData['enupalStripe'];
        $token = $data['token'] ?? null;
        $formId = $data['formId'] ?? null;
        $paymentType = $postData['paymentType'] ?? PaymentType::CC;
        $data['couponCode'] = $postData['enupalCouponCode'] ?? null;
        $settings = StripePlugin::$app->settings->getSettings();

        if (empty($token) || empty($formId)){
            Craft::error('Unable to get the stripe token or formId', __METHOD__);
            return $result;
        }

        $amount = $data['amount'] ?? null;

        if ($amount === '' || $amount == 'NaN' || is_null($amount)){
            Craft::error('Unable to get the final amount from the post request', __METHOD__);
            return $result;
        }

        $paymentForm = StripePlugin::$app->paymentForms->getPaymentFormById((int)$formId);

        if (is_null($paymentForm)) {
            throw new \Exception(Craft::t('enupal-stripe','Unable to find the Stripe Button associated to the order'));
        }

        if ($paymentType == PaymentType::IDEAL || $paymentType == PaymentType::SOFORT){
            $paymentForm->currency = 'EUR';
        }

        if (is_null($order)){
            $order = $this->populateOrder($data);
        }

        if ($paymentType != null){
            // Possible card element
            $order->paymentType = $paymentType;
        }

        $order->currency = $paymentForm->currency;
        $data['currency'] = $paymentForm->currency;
        $order->formId = $paymentForm->id;

        StripePlugin::$app->settings->initializeStripe();

        $isNew = false;
        $stripeId = null;

        if (!$settings->useSca){
            $customer = $this->getCustomer($order, $token, $isNew, $order->testMode);

            if (is_null($customer)){
                return $order;
            }

            if ($paymentForm->enableSubscriptions){
                $stripeId = $this->handleSubscription($paymentForm, $customer, $data, $order);
            }else{
                $stripeId = $this->handleOneTimePayment($paymentForm, $customer, $data, $token, $isNew, $order);
            }

            if (is_null($stripeId)){
                Craft::error('Something went wrong making the charge to Stripe. -CHECK PREVIOUS LOGS-', __METHOD__);
                return $order;
            }

            if ($data['couponCode'] && ($paymentType == PaymentType::IDEAL || $paymentType==PaymentType::SOFORT)){
                $order->totalPrice = $this->convertFromCents($order->totalPrice, $paymentForm->currency);
            }
        }else{
            // The payment intent should be already processed
            $stripeId = $token;
        }

        $order->stripeTransactionId = $stripeId;
        $order->isSubscription = StripePlugin::$app->subscriptions->getIsSubscription($order->stripeTransactionId);

        // revert cents - Async charges already make this conversion - On Checkout $paymentType is null
        if ($paymentType == PaymentType::CC || is_null($paymentType)){
            $order->totalPrice = $this->convertFromCents($order->totalPrice, $paymentForm->currency);

            if (!$order->isSubscription){
                $pluginSettings = StripePlugin::$app->settings->getSettings();
                if (!$pluginSettings->capture){
                    $order->isCompleted = false;
                }
            }
        }

        $order = $this->finishOrder($order, $paymentForm);

        return $order;
    }

    /**
     * @param $planId
     * @param $paymentForm
     * @return null|float
     */
    public function getSetupFeeFromMatrix($planId, $paymentForm)
    {
        foreach ($paymentForm->enupalMultiplePlans->all() as $plan) {
            if ($plan->selectPlan == $planId){
                if ($plan->setupFee){
                    return $plan->setupFee;
                }
            }
        }

        return null;
    }

    /**
     * @param $email
     * @return null|string
     */
    public function getCustomerReference($email)
    {
        $customer = (new Query())
            ->select(['stripeId'])
            ->from('{{%enupalstripe_customers}}')
            ->where(Db::parseParam('email', $email))
            ->one();

        return $customer['stripeId'] ?? null;
    }

    /**
     * @param $eventJson
     * @param $order
     */
    public function triggerWebhookEvent($eventJson, $order)
    {
        Craft::info("Triggering Webhook event", __METHOD__);

        $event = new WebhookEvent([
            'stripeData' => $eventJson,
            'order' => $order
        ]);

        $this->trigger(self::EVENT_AFTER_PROCESS_WEBHOOK, $event);
    }

    /**
     * @param $order
     */
    public function triggerOrderRefundEvent($order)
    {
        Craft::info("Triggering order refund event", __METHOD__);

        $event = new OrderRefundEvent([
            'order' => $order
        ]);

        $this->trigger(self::EVENT_AFTER_REFUND_ORDER, $event);
    }

    /**
     * @param $order
     */
    public function triggerOrderCaptureEvent($order)
    {
        Craft::info("Triggering order capture event", __METHOD__);

        $event = new OrderCaptureEvent([
            'order' => $order
        ]);

        $this->trigger(self::EVENT_AFTER_CAPTURE_ORDER, $event);
    }

    /**
     * Stripe Charge given the config array
     *
     * @param $settings
     * @param Order $order
     * @return null|\Stripe\ApiResource
     */
    public function charge($settings, $order)
    {
        $charge = null;
        $pluginSettings = StripePlugin::$app->settings->getSettings();
        if (!$pluginSettings->capture){
            $settings['capture'] = false;
        }

        try {
            $charge = Charge::create($settings);
        } catch (\Exception $e) {
            // Something else happened, completely unrelated to Stripe
            Craft::error('Stripe - something went wrong: '.$e->getMessage(), __METHOD__);
            $order->addError('stripePayments', $e->getMessage());
        }

        return $charge;
    }

    /**
     * @param $amount
     * @param $currency
     * @return float|int
     */
    public function convertToCents($amount, $currency)
    {
        if ($this->hasZeroDecimals($currency)){
            return (int)$amount;
        }

        return $amount * 100;
    }

    /**
     * @param $amount
     * @param $currency
     * @return float|int
     */
    public function convertFromCents($amount, $currency)
    {
        if ($this->hasZeroDecimals($currency)){
            return $amount;
        }

        return $amount / 100;
    }

    /**
     * @param $order Order
     * @return bool
     * @throws \Throwable
     */
    public function refundOrder(Order $order)
    {
        $result = false;

        if (!$order->refunded) {
            try {
                StripePlugin::$app->settings->initializeStripe();
                $stripeId = $this->getChargeIdFromOrder($order);
                $options = [];

                if (!$order->isSubscription()){
                    // Async transactions have py_ (payment) as stripeId and an amount is required
                    if ($order->paymentType == PaymentType::IDEAL || $order->paymentType == PaymentType::SOFORT){
                        $options['amount'] = $this->convertToCents($order->totalPrice, $order->currency);
                    }
                }

                $options = [
                    'charge' => $stripeId
                ];

                $refund = Refund::create($options);

                if ($refund['status'] == 'succeeded' || $refund['status'] == 'pending') {
                    $order->refunded = true;
                    $now = Db::prepareDateForDb(new \DateTime());
                    $order->dateRefunded = $now;
                    $this->saveOrder($order, false);
                    Stripe::$app->messages->addMessage($order->id,'Control Panel - Payment Refunded', $refund);

                    $this->triggerOrderRefundEvent($order);
                    $result = true;
                }else{
                    Stripe::$app->messages->addMessage($order->id,'Control Panel - Something went wrong on Refund process', $refund);
                }
            } catch (\Exception $e) {
                Stripe::$app->messages->addMessage($order->id,'Control Panel - Failed to refund payment', ['error' => $e->getMessage()]);
            }
        }else{
            Stripe::$app->messages->addMessage($order->id, "Control Panel - Payment was already refunded", []);
        }

        return $result;
    }

    /**
     * @param $order Order
     * @return bool
     * @throws \Throwable
     */
    public function captureOrder(Order $order)
    {
        $result = false;
        $stripeId = $order->stripeTransactionId;

        if ($order->isSubscription()){
            $invoices = Invoice::all(['subscription' => $stripeId]);
            if (isset($invoices['data'][0])){
                $firstInvoice = $invoices['data'][0];
                $stripeId = $firstInvoice['charge'];
            }
        }

        $charge = $this->getCharge($stripeId);

        if (!$charge->captured) {
            try {
            	if ($charge->payment_intent){
		            $intent = StripePlugin::$app->paymentIntents->getPaymentIntent($charge->payment_intent);
		            $paymentIntent = $intent->capture();
		            $response = $paymentIntent['charges']['data'][0];

	            }else{
		            $response = $charge->capture();
	            }

                if (isset($response['captured']) && $response['captured']) {
                    $order->isCompleted = true;
                    $this->saveOrder($order, false);
                    Stripe::$app->messages->addMessage($order->id,'Control Panel - Payment captured', $response);

                    $this->triggerOrderCaptureEvent($order);
                    $result = true;
                }else{
                    Stripe::$app->messages->addMessage($order->id,'Control Panel - Something went wrong on Capture process', $response);
                }
            } catch (\Exception $e) {
                Stripe::$app->messages->addMessage($order->id,'Control Panel - Failed to refund payment', ['error' => $e->getMessage()]);
            }
        }else{
            Stripe::$app->messages->addMessage($order->id, "Control Panel - Payment was already captured", []);
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return string
     * @throws \Exception
     */
    public function getChargeIdFromOrder(Order $order)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripeId = $order->stripeTransactionId;

        if ($order->isSubscription()){
            $invoices = Invoice::all(['subscription' => $stripeId]);
            if (isset($invoices['data'][0])){
                $firstInvoice = $invoices['data'][0];
                $stripeId = $firstInvoice['charge'];
            }
        }

        return $stripeId;
    }

    /**
     * @param $userId
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByUser($userId)
    {
        $query = Order::find();
        $query->userId = $userId;

        return $query->all();
    }

    /**
     * @param $email
     * @return array|\craft\base\ElementInterface|null
     */
    public function getOrdersByEmail($email)
    {
        $query = Order::find();
        $query->email = $email;

        return $query->all();
    }

    /**
     * Return the Minimum charge by currency
     *
     * @link https://stripe.com/docs/currencies#minimum-and-maximum-charge-amounts
     * @param $currency
     * @return mixed|string
     */
    public function getMinimumChargeInCents($currency)
    {
        // Default for USD, BRL, CAD, AUD, CHF, EUR, NZD, SGD
        $default = '0.50';

        $minimumCharges = [
            'DKK' => '2.50',
            'GBP' => '4.00',
            'JPY' => '50',
            'MXN' => '10',
            'NOK' => '3.00',
            'SEK' => '3.00'
        ];

        $minimum = $minimumCharges[$currency] ?? $default;

        return $this->convertToCents($minimum, $currency);
    }

    /**
     * @param $charge
     * @return Charge
     * @throws \Exception
     */
    public function getCharge($charge)
    {
        StripePlugin::$app->settings->initializeStripe();

        $charge = Charge::retrieve($charge);

        return $charge;
    }

    /**
     * @param $addressData
     * @return int
     */
    private function getAddressId($addressData)
    {
        $address = new Address();

        $countryId = $addressData['country'] ?? null;

        if (!is_int($countryId)){
            $country = Stripe::$app->countries->getCountryByIso($countryId);
            if ($country){
                $countryId = $country->id;
            }
        }

        $address->city = $addressData['city'] ?? '';
        $address->countryId = $countryId;
        $address->stateName = $addressData['state'] ?? '';
        $address->zipCode = $addressData['zip'] ?? '';
        $address->firstName = $addressData['name'] ?? '';
        $address->address1 = $addressData['line1'] ?? '';

        Stripe::$app->addresses->saveAddress($address, true);

        return $address->id;
    }

    /**
     * Create Special SEPA Direct Debit Source object to make recurring payments with asynchronous sources
     *
     * @param $token
     * @param $sourceObject
     * @param $type
     * @return mixed|null
     */
    private function getSepaSource($token, $sourceObject, $type)
    {
        $name = $sourceObject['data']['owner']['verified_name'] ?? $sourceObject['data']['owner']['name'] ?? 'Jenny Rosen';

        $source = Source::create(array(
            "type" => "sepa_debit",
            "sepa_debit" => array($type => $token),
            "currency" => "eur",
            "owner" => array(
                "name" => $name,
            ),
        ));

        return $source['id'];
    }

    /**
     * @param $data
     * @param $paymentForm
     * @param $customer
     * @param $isNew
     * @param $token
     * @param $order
     * @return \Stripe\ApiResource|null
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    private function stripeCharge($data, $paymentForm, $customer, $isNew, $token, $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();
        $description = Craft::$app->getView()->renderObjectTemplate($settings->chargeDescription, $order);
        $charge = null;
        $addressData = $data['address'] ?? null;

        if (!$isNew){
            // Set as default the new chargeable
            if ($order->paymentType == PaymentType::IDEAL || $order->paymentType == PaymentType::SOFORT){
                $customer->default_source = $token;
                $customer->save();
            }
        }

        if ($data['couponCode']){
            $this->applyCouponToOrder($data, $order);
        }

        $chargeSettings = [
            'amount' => $data['amount'], // amount in cents from js
            'currency' => $paymentForm->currency,
            'customer' => $customer->id,
            'description' => $description,
            'metadata' => $this->getStripeMetadata($data),
            'shipping' => $addressData ? $this->getShipping($addressData) : []
        ];

        $charge = $this->charge($chargeSettings, $order);

        return $charge;
    }

    /**
     * We should throw exceptions only if dev mode is enabled, when the site is live check error logs.
     * @param $e
     */
    private function throwException($e)
    {
        if (Craft::$app->getConfig()->general->devMode) {
            throw $e;
        }
    }

    /**
     * Add a plan to a customer
     *
     * @param $customer
     * @param $planId
     * @param $data
     * @param $order
     * @return mixed
     * @throws \Exception
     */
    private function addPlanToCustomer($customer, $planId, $data, $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        //Get the plan from stripe it would trow an exception if the plan does not exists
        $plan = StripePlugin::$app->plans->getStripePlan($planId);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planId,
            "trial_from_plan" => true
        ];

        if ($data['couponCode']){
            $this->applyCouponToOrder($data, $order, true);
            $subscriptionSettings['coupon'] = $order->couponCode;
        }

        // Add support for tiers
        if ($plan['usage_type'] !== 'metered'){
            if (isset($data['quantity']) && $data['quantity']){
                $subscriptionSettings['quantity'] = $data['quantity'];
            }
        }

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $data
     * @param Order $order
     * @param bool $isRecurring
     * @throws \Exception
     */
    private function applyCouponToOrder(&$data, Order &$order, $isRecurring = false)
    {
        $couponRedeemed = StripePlugin::$app->coupons->applyCouponToAmountInCents($data['amount'], $data['couponCode'], $order->currency, $isRecurring);

        if ($couponRedeemed->isValid){
            $coupon = $couponRedeemed->coupon;
            $couponAmount = $data['amount'] - $couponRedeemed->finalAmount;
            $order->couponCode = $coupon['id'];
            $order->couponName = $coupon['name'];
            $order->couponAmount = $this->convertFromCents($couponAmount, $order->currency);
            $order->couponSnapshot = json_encode($coupon);
            $order->totalPrice = $couponRedeemed->finalAmount;

            $data['metadata']['couponCode'] = $coupon['id'];
            $data['amount'] = $couponRedeemed->finalAmount;
        }
    }

    /**
     * @param $customer
     * @param $data
     * @param $paymentForm
     * @param $order
     * @return mixed
     * @throws \Exception
     */
    private function addRecurringPayment($customer, $data, $paymentForm, $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();
        $customPlan = new CustomPlan([
            "amountInCents" => $data['amount'],
            "interval" => $paymentForm->recurringPaymentType,
            "currency" => $paymentForm->currency
        ]);

        // Remove tax from amount
        if ($settings->enableTaxes && $settings->tax){
            $currentAmount = $this->convertFromCents($data['amount'], $paymentForm->currency);
            $beforeTax = $currentAmount - $data['taxAmount'];
            $data['amount'] = $this->convertToCents($beforeTax, $paymentForm->currency);
            $customPlan->amountInCents = $data['amount'];
        }

        //Create new plan for this customer:
        $plan = StripePlugin::$app->plans->createCustomPlan($customPlan);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $plan['id']
        ];

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        if ($data['couponCode']){
            $this->applyCouponToOrder($data, $order, true);
            $subscriptionSettings['coupon'] = $order->couponCode;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $customer
     * @param $data
     * @param $paymentForm
     * @return mixed
     */
    private function addCustomPlan($customer, $data, $paymentForm)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        // Remove tax from amount
        if ($settings->enableTaxes && $settings->tax){
            $currentAmount = $this->convertFromCents($data['amount'], $paymentForm->currency);
            $beforeTax = $currentAmount - $data['taxAmount'];
            $data['amount'] = $this->convertToCents($beforeTax, $paymentForm->currency);
        }

        if ($paymentForm->singlePlanSetupFee){
            $currentAmount = $this->convertFromCents($data['amount'], $paymentForm->currency);
            $beforeFee = $currentAmount - $paymentForm->singlePlanSetupFee;
            $data['amount'] = $this->convertToCents($beforeFee, $paymentForm->currency);
        }

        $customPlan = new CustomPlan([
            "amountInCents" => $data['amount'],
            "interval" => $data['customFrequency'] ?? $paymentForm->customPlanFrequency,
            "intervalCount" => $data['customInterval'] ?? $paymentForm->customPlanInterval,
            "currency" => $paymentForm->currency
        ]);

        $trialPeriodDays = $data['customTrialPeriodDays'] ?? $paymentForm->singlePlanTrialPeriod;

        if ($trialPeriodDays){
            $customPlan->trialPeriodDays = $trialPeriodDays;
        }

        $plan = StripePlugin::$app->plans->createCustomPlan($customPlan);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $plan['id'],
            "trial_from_plan" => true
        ];

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $customer
     * @param $amount
     * @param $paymentForm
     */
    public function addOneTimeSetupFee($customer, $amount, $paymentForm)
    {
        InvoiceItem::create(
            [
                "customer" => $customer->id,
                "amount" => $this->convertToCents($amount, $paymentForm->currency),
                "currency" => $paymentForm->currency,
                "description" => "One-time setup fee: ".$paymentForm->name
            ]
        );
    }

    /**
     * @param $currency
     * @return bool
     */
    private function hasZeroDecimals($currency)
    {
        $currency = strtoupper($currency);
        $zeroDecimals = ['MGA', 'BIF', 'CLP', 'PYG', 'DJF', 'RWF', 'GNF', 'UGX', 'JPY', 'VND', 'VUV', 'XAF', 'KMF', 'KRW', 'XOF', 'XPF'];

        foreach ($zeroDecimals as $zeroDecimal) {
            if ($zeroDecimal == $currency){
                return true;
            }
        }

        return false;
    }

    /**
     * Get a Stripe Customer Object
     *
     * @param Order $order
     * @param $token
     * @param $isNew
     * @param bool $testMode
     * @return null|\Stripe\ApiResource|\Stripe\StripeObject
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function getCustomer($order, $token, &$isNew, $testMode = true)
    {
        $stripeCustomer = null;
        $testMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN);
        // Check if customer exists
        $customerRecord = CustomerRecord::findOne([
            'email' => $order->email,
            'testMode' => $testMode
        ]);

        if ($customerRecord){
            $customerId = $customerRecord->stripeId;
            try{
                $stripeCustomer = Customer::retrieve($customerId);
            }
            catch (\Exception $e){
                $stripeCustomer = null;
                Craft::info($e->getMessage(). " - Creating a new customer");
            }

            if ((isset($stripeCustomer->deleted) && $stripeCustomer->deleted) || is_null($stripeCustomer)){
                Craft::info("Deleting customer: ".$customerRecord->id, __METHOD__);
                $stripeCustomer = null;
                $customerRecord->delete();
            }
        }

        if (!isset($stripeCustomer->id)){

            try {
                $stripeCustomer = Customer::create([
                    'email' => $order->email,
                    'source' => $token
                ]);
            } catch (\Exception $e) {
                Craft::error('Stripe Error creating customer: ' . $e->getMessage(), __METHOD__);
                $order->addError('stripePayments', $e->getMessage());
                return null;
            }

            StripePlugin::$app->customers->createCustomer($order->email, $stripeCustomer->id, $testMode);
            $isNew = true;
        }else{
            // Add support for Stripe API 2018-07-27
            $stripeCustomer->source = $token;
            try {
                $stripeCustomer->save();
            } catch (\Exception $e) {
                Craft::error('Stripe Error saving customer: ' . $e->getMessage(), __METHOD__);
                $order->addError('stripePayments', $e->getMessage());
                return null;
            }
        }

        return $stripeCustomer;
    }

    /**
     * @param $data
     * @return array
     */
    public function getStripeMetadata($data)
    {
        $metadata = [];
        if (isset($data['metadata'])){
            foreach ($data['metadata'] as $key => $item) {
                if (is_array($item)){
                    $value = '';
                    // Checkboxes and if we add multi-select. lets concatenate the selected values
                    $pos = 0;
                    foreach ($item as $val) {
                        if ($pos == 0){
                            $value = $val;
                        }else{
                            $value .= ', '.$val;
                        }
                        $pos++;
                    }
                    $metadata[$key] = $value;
                }else{
                    $metadata[$key] = $item;
                }
            }
        }

        return $metadata;
    }

    /**
     * @param $postData
     *
     * @return array
     */
    private function getShipping($postData)
    {
        // Add shipping information if enable
        $shipping = [
            "name" => $postData['name'] ?? '',
            "address" => [
                "city" => $postData['city'] ?? '',
                "country" => $postData['country'] ?? '',
                "line1" => $postData['line1'] ?? '',
                "postal_code" => $postData['zip'] ?? '',
                "state" => $postData['state'] ?? '',
            ]
        ];

        return $shipping;
    }

    /**
     * @param $postData
     *
     * @return array
     */
    private function getBillingAddress($postData)
    {
        // Add billing information if enable
        $shipping = [
            "city" => $postData['city'] ?? '',
            "country" => $postData['country'] ?? '',
            "line1" => $postData['line1'] ?? '',
            "postal_code" => $postData['zip'] ?? '',
            "state" => $postData['state'] ?? '',
        ];

        return $shipping;
    }


    /**
     * @param $paymentForm
     * @param $customer
     * @param $data
     * @param $token
     * @param $isNew
     * @param $order
     * @return mixed|null
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    private function handleOneTimePayment($paymentForm, $customer, $data, $token, $isNew, $order)
    {
        $stripeId = null;
        // One time payment could be a subscription
        if (isset($data['recurringToggle']) && $data['recurringToggle'] == 'on'){
            if (isset($data['customAmount']) && $data['customAmount'] > 0){
                // test what is returning we need a stripe id
                $subscription = $this->addRecurringPayment($customer, $data, $paymentForm, $order);
                $stripeId = $subscription->id ?? null;
            }
        }

        if (is_null($stripeId)){
            $charge = $this->stripeCharge($data, $paymentForm, $customer, $isNew, $token, $order);
            $stripeId = $charge['id'] ?? null;
        }

        return $stripeId;
    }

    /**
     * @param $paymentForm
     * @param $customer
     * @param $data
     * @param Order $order
     * @return null
     * @throws \Exception
     */
    private function handleSubscription($paymentForm, $customer, $data, $order)
    {
        $planId = null;
        $stripeId = null;

        $shippingAddress = $data['address'] ?? null;
        $billingAddress = $data['billingAddress'] ?? null;
        $sameAddress = $data['sameAddressToggle'] ?? null;
        $isIncomplete = false;

        if ($billingAddress !== null){
            $params = [
                'address' => $billingAddress ? $this->getBillingAddress($billingAddress) : []
            ];

            StripePlugin::$app->customers->updateStripeCustomer($customer->id, $params);
            if ($sameAddress == 'on'){
                $shippingAddress = $billingAddress;
            }
        }

        if ($shippingAddress !== null){
            $params = [
                'shipping' => $shippingAddress ? $this->getShipping($shippingAddress) : []
            ];
            StripePlugin::$app->customers->updateStripeCustomer($customer->id, $params);
        }

        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && !$paymentForm->enableCustomPlanAmount){
            $plan = Json::decode($paymentForm->singlePlanInfo, true);
            $planId = $plan['id'];

            // Lets create an invoice item if there is a setup fee
            if ($paymentForm->singlePlanSetupFee && $paymentForm->singlePlanSetupFee > 0){
                $this->addOneTimeSetupFee($customer, $paymentForm->singlePlanSetupFee, $paymentForm);
            }

            // Either single plan or multiple plans the user should select one plan and plan id should be available in the post request
            $subscription = $this->addPlanToCustomer($customer, $planId, $data, $order);
            $isIncomplete = $this->getSubscriptionIsIncomplete($subscription->status);
            $stripeId = $subscription->id ?? null;
        }

        if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && $paymentForm->enableCustomPlanAmount) {
            if (isset($data['customPlanAmount']) && $data['customPlanAmount'] > 0){
                // Lets create an invoice item if there is a setup fee
                if ($paymentForm->singlePlanSetupFee){
                    $this->addOneTimeSetupFee($customer, $paymentForm->singlePlanSetupFee, $paymentForm);
                }
                // test what is returning we need a stripe id
                $subscription = $this->addCustomPlan($customer, $data, $paymentForm);
                $isIncomplete = $this->getSubscriptionIsIncomplete($subscription->status);
                $stripeId = $subscription->id ?? null;
            }
        }

        if ($paymentForm->subscriptionType == SubscriptionType::MULTIPLE_PLANS) {
            $planId = $data['enupalMultiPlan'] ?? null;

            if (is_null($planId) || empty($planId)){
                throw new \Exception(Craft::t('enupal-stripe','Plan Id is required'));
            }

            $setupFee = $this->getSetupFeeFromMatrix($planId, $paymentForm);

            if ($setupFee){
                $this->addOneTimeSetupFee($customer, $setupFee, $paymentForm);
            }

            $subscription = $this->addPlanToCustomer($customer, $planId, $data, $order);
            $isIncomplete = $this->getSubscriptionIsIncomplete($subscription->status);
            $stripeId = $subscription->id ?? null;
        }

        if ($isIncomplete) {
            $order->addError('stripePayments', Craft::t('site', 'An error occurred while processing the card. Try again later or with a different payment method'));
            return null;
        }

        return $stripeId;
    }

    /**
     * @param $status
     * @return bool
     */
    private function getSubscriptionIsIncomplete($status)
    {
        return $status === 'incomplete' ? true : false;
    }

	/**
	 * @param $order
	 * @param $paymentForm
	 *
	 * @return null|Order
	 * @throws \Throwable
	 * @throws \yii\base\Exception
	 */
    private function finishOrder($order, $paymentForm)
    {
        $result = null;
        // Stock
        $savePaymentForm = false;
        if (!$paymentForm->hasUnlimitedStock && (int)$paymentForm->quantity > 0){
            $paymentForm->quantity -= $order->quantity;
            $savePaymentForm = true;
        }

        // Finally save the order in Craft CMS
        if (!StripePlugin::$app->orders->saveOrder($order)){
            Craft::error('Something went wrong saving the Stripe Order: '.json_encode($order->getErrors()), __METHOD__);
            return $result;
        }

        // Let's update the stock
        if ($savePaymentForm){
            if (!StripePlugin::$app->paymentForms->savePaymentForm($paymentForm, false)){
                Craft::error('Something went wrong updating the payment form stock: '.json_encode($paymentForm->getErrors()), __METHOD__);
                return $result;
            }
        }

        Craft::info('Enupal Stripe - Order Created: './** @scrutinizer ignore-type */ $order->number, __METHOD__);
        $result = $order;

        return $result;
    }

    /**
     * @return mixed
     */
    private function getPostData()
    {
        $postData = $_POST;
        unset($postData['CRAFT_CSRF_TOKEN']);
        unset($postData['action']);
        unset($postData['redirect']);

        return $postData;
    }
}
