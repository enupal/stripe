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
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use enupal\stripe\elements\Order;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\enums\PaymentType;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\events\NotificationEvent;
use enupal\stripe\events\OrderCompleteEvent;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\Card;
use Stripe\InvoiceItem;
use Stripe\Plan;
use Stripe\Source;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
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
     * @event NotificationEvent The event that is triggered before a notification is send
     *
     * Plugins can get notified before a notification email is send
     *
     * ```php
     * use enupal\stripe\events\NotificationEvent;
     * use enupal\stripe\services\Orders;
     * use yii\base\Event;
     *
     * Event::on(Orders::class, Orders::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, function(NotificationEvent $e) {
     *      $message = $e->message;
     *     // Do something
     * });
     * ```
     */
    const EVENT_BEFORE_SEND_NOTIFICATION_EMAIL = 'beforeSendNotificationEmail';

    /**
     * Returns a Order model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|\craft\base\ElementInterface
     */
    public function getOrderById(int $id, int $siteId = null)
    {
        $order = Craft::$app->getElements()->getElementById($id, Order::class, $siteId);

        return $order;
    }

    /**
     * Returns a Order model if one is found in the database by number
     *
     * @param string $number
     * @param int    $siteId
     *
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderByNumber($number, int $siteId = null)
    {
        $query = Order::find();
        $query->number($number);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns a Order model if one is found in the database by stripe transaction id
     *
     * @param string $stripeTransactionId
     * @param int    $siteId
     *
     * @return null|\craft\base\ElementInterface|Order
     */
    public function getOrderByStripeId($stripeTransactionId, int $siteId = null)
    {
        $query = Order::find();
        $query->stripeTransactionId($stripeTransactionId);
        $query->siteId($siteId);

        return $query->one();
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
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveOrder(Order $order)
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
            if (Craft::$app->elements->saveElement($order)) {
                $transaction->commit();

                if ($order->orderStatusId == OrderStatus::NEW){
                    $event = new OrderCompleteEvent([
                        'order' => $order
                    ]);

                    $this->trigger(self::EVENT_AFTER_ORDER_COMPLETE, $event);
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
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
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
     * @return array
     */
    public function getColorStatuses()
    {
        $colors = [
            OrderStatus::PENDING => 'white',
            OrderStatus::NEW => 'green',
            OrderStatus::PROCESSED => 'blue',
        ];

        return $colors;
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
     * @param Order $order
     *
     * @return bool
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function sendCustomerNotification(Order $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->enableCustomerNotification) {
            return false;
        }

        $variables = [];
        $view = Craft::$app->getView();
        $message = new Message();
        $message->setFrom([$settings->customerNotificationSenderEmail => $settings->customerNotificationSenderName]);
        $variables['order'] = $order;
        $subject = $view->renderString($settings->customerNotificationSubject, $variables);
        $textBody = $view->renderString("Thank you! your order number is: {{order.number}}", $variables);

        $originalPath = $view->getTemplatesPath();

        $template = 'customer';
        $templateOverride = null;
        $extensions = ['.html', '.twig'];

        if ($settings->customerTemplateOverride){
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$settings->customerTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $settings->customerTemplateOverride;
                    $template = $templateOverride;
                }
            }
        }

        if (is_null($templateOverride)){
            $view->setTemplatesPath($this->getEmailsPath());
        }

        $htmlBody = $view->renderTemplate($template, $variables);

        $view->setTemplatesPath($originalPath);

        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);
        $message->setReplyTo($settings->customerNotificationReplyToEmail);
        // customer email
        $emails = [$order->email];
        $message->setTo($emails);

        $mailer = Craft::$app->getMailer();

        $event = new NotificationEvent([
            'message' => $message,
            'type' => 'customer'
        ]);

        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, $event);

        try {
            $result = $mailer->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $result = false;
        }

        if (!$result) {
            Craft::error('Unable to send customer email', __METHOD__);
        }

        Craft::info('Customer email sent successfully', __METHOD__);

        return $result;
    }

    /**
     * @param Order $order
     *
     * @return bool
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function sendAdminNotification(Order $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->enableAdminNotification) {
            return false;
        }

        $variables = [];
        $view = Craft::$app->getView();
        $message = new Message();
        $message->setFrom([$settings->adminNotificationSenderEmail => $settings->adminNotificationSenderName]);
        $variables['order'] = $order;
        $subject = $view->renderString($settings->adminNotificationSubject, $variables);
        $textBody = $view->renderString("Congratulations! you have received a payment, total: {{ order.totalPrice }} order number: {{order.number}}", $variables);

        $originalPath = $view->getTemplatesPath();
        $template = 'admin';
        $templateOverride = null;
        $extensions = ['.html', '.twig'];

        if ($settings->adminTemplateOverride){
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$settings->adminTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $settings->adminTemplateOverride;
                    $template = $templateOverride;
                }
            }
        }

        if (is_null($templateOverride)){
            $view->setTemplatesPath($this->getEmailsPath());
        }

        $htmlBody = $view->renderTemplate($template, $variables);

        $view->setTemplatesPath($originalPath);

        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);
        $message->setReplyTo($settings->adminNotificationReplyToEmail);

        $emails = explode(",", $settings->adminNotificationRecipients);
        $message->setTo($emails);

        $mailer = Craft::$app->getMailer();

        $event = new NotificationEvent([
            'message' => $message,
            'type' => 'admin'
        ]);

        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, $event);

        try {
            $result = $mailer->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $result = false;
        }

        if (!$result) {
            Craft::error('Unable to send admin email', __METHOD__);
        }

        Craft::info('Admin email sent successfully', __METHOD__);

        return $result;
    }

    /**
     * @return bool|string
     */
    public function getEmailsPath()
    {
        $defaultTemplate = Craft::getAlias('@enupal/stripe/templates/_emails/');

        return $defaultTemplate;
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
        $order = new Order();
        $order->orderStatusId = $isPending ? OrderStatus::PENDING : OrderStatus::NEW;
        $order->number = $this->getRandomStr();
        $order->email = $data['email'];
        $order->totalPrice = $data['amount'];// The amount come in cents, we revert this just before save the order
        $order->quantity = $data['quantity'] ?? 1;
        $order->shipping = $data['shippingAmount'] ?? 0;
        $order->tax = $data['taxAmount'] ?? 0;
        $order->discount = $data['discountAmount'] ?? 0;
        // Shipping
        if (isset($data['address'])){
            $order->addressCity = $data['address']['city'] ?? '';
            $order->addressCountry = $data['address']['country'] ?? '';
            $order->addressState = $data['address']['state'] ?? '';
            $order->addressCountryCode = $data['address']['zip'] ?? '';
            $order->addressName = $data['address']['name'] ?? '';
            $order->addressStreet = $data['address']['line1'] ?? '';
            $order->addressZip = $data['address']['zip'] ?? '';
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
     * Process Ideal Payment
     *
     * @return array|null
     * @throws \Throwable
     * @throws \craft\errors\SiteNotFoundException
     */
    public function processIdealPayment()
    {
        $result = null;
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('enupalStripe');
        $email = $request->getBodyParam('stripeElementEmail') ?? null;
        $formId = $data['formId'] ?? null;
        $data['email'] = $email;

        if (empty($email) || empty($formId)){
            Craft::error('Unable to get the Full name, formId or email', __METHOD__);
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
        $options = [];

        if (isset($postData['address'])){
            $data['address'] = $postData['address'];
            $options['address'] = $this->getStripeAddress($data['address']);
        }

        $order = $this->populateOrder($data, true);
        $order->paymentType = $request->getBodyParam('paymentType');
        $order->postData = json_encode($postData);
        $order->currency = 'EUR';
        $order->formId = $paymentForm->id;

        $redirect = UrlHelper::siteUrl($paymentForm->returnUrl) ?? Craft::getAlias(Craft::$app->getSites()->getPrimarySite()->baseUrl);

        StripePlugin::$app->settings->initializeStripe();

        $source = Source::create([
            'type' => 'ideal',
            'amount' => $amount,
            'currency' => 'eur',
            'owner' => ['email' => $email],
            'redirect' => ['return_url' => $redirect],
            'metadata' => $this->getStripeMetadata($data)
        ], $options);

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
     * @return Order|null
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function idealCharge($order, $sourceObject)
    {
        $token = $order->stripeTransactionId;
        StripePlugin::$app->settings->initializeStripe();
        $paymentForm = $order->getPaymentForm();
        $postData = json_decode($order->postData, true);
        $data = $postData['enupalStripe'];

        if ($paymentForm->enableSubscriptions || (isset($data['recurringToggle']) && $data['recurringToggle'] == 'on')) {
            // Override iDEAL source token with SEPA source token for recurring payments
            $token = $this->getSepaSourceWithIdeal($token);
        }

        $postData['enupalStripe']['token'] = $token;
        $postData['enupalStripe']['amount'] = $sourceObject['data']['object']['amount'];
        $postData['enupalStripe']['currency'] = $sourceObject['data']['object']['currency'];

        $order->orderStatusId = OrderStatus::NEW;

        $order = $this->processPayment($postData, $order);

        return $order;
    }

    /**
     * Create Special SEPA Direct Debit Source object to make recurring payments with iDEAL
     * @param $token
     * @return string|null
     */
    private function getSepaSourceWithIdeal($token)
    {
        $source = Source::create(array(
            "type" => "sepa_debit",
            "sepa_debit" => array("ideal" => $token),
            "currency" => "eur",
            "owner" => array(
                "name" => "Jenny Rosen",
            ),
        ));

        return $source['id'];
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
        $paymentType = $postData['paymentType'] ?? null;

        if (empty($token) || empty($formId)){
            Craft::error('Unable to get the stripe token or formId', __METHOD__);
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

        if ($paymentType == PaymentType::IDEAL){
            $paymentForm->currency = 'EUR';
        }

        if (is_null($order)){
            $order = $this->populateOrder($data);
        }
        $order->currency = $paymentForm->currency;
        $order->formId = $paymentForm->id;

        StripePlugin::$app->settings->initializeStripe();

        $isNew = false;
        $customer = $this->getCustomer($order->email, $token, $isNew, $order->testMode);

        $charge = null;
        $stripeId = null;

        if ($paymentForm->enableSubscriptions){
            $planId = null;

            if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && !$paymentForm->enableCustomPlanAmount){
                $plan = Json::decode($paymentForm->singlePlanInfo, true);
                $planId = $plan['id'];

                // Lets create an invoice item if there is a setup fee - @todo support iDEAL with SEPA?
                if ($paymentForm->singlePlanSetupFee && $paymentForm->enableCheckout){
                    $this->addOneTimeSetupFee($customer, $paymentForm->singlePlanSetupFee, $paymentForm);
                }

                // Either single plan or multiple plans the user should select one plan and plan id should be available in the post request
                $subscription = $this->addPlanToCustomer($customer, $planId, $token, $isNew, $data);
                $stripeId = $subscription->id ?? null;
            }

            if ($paymentForm->subscriptionType == SubscriptionType::SINGLE_PLAN && $paymentForm->enableCustomPlanAmount) {
                if (isset($data['customPlanAmount']) && $data['customPlanAmount'] > 0){
                    // Lets create an invoice item if there is a setup fee
                    if ($paymentForm->singlePlanSetupFee && $paymentForm->enableCheckout){
                        $this->addOneTimeSetupFee($customer, $paymentForm->singlePlanSetupFee, $paymentForm);
                    }
                    // test what is returning we need a stripe id
                    $subscription = $this->addCustomPlan($customer, $data, $paymentForm, $token, $isNew);
                    $stripeId = $subscription->id ?? null;
                }
            }

            if ($paymentForm->subscriptionType == SubscriptionType::MULTIPLE_PLANS) {
                $planId = $data['enupalMultiPlan'] ?? null;

                if (is_null($planId) || empty($planId)){
                    throw new \Exception(Craft::t('enupal-stripe','Plan Id is required'));
                }

                $setupFee = $this->getSetupFeeFromMatrix($planId, $paymentForm);

                if ($setupFee && $paymentForm->enableCheckout){
                    $this->addOneTimeSetupFee($customer, $setupFee, $paymentForm);
                }

                $subscription = $this->addPlanToCustomer($customer, $planId, $token, $isNew, $data);
                $stripeId = $subscription->id ?? null;
            }
        }else{
            // One time payment could be a subscription
            if (isset($data['recurringToggle']) && $data['recurringToggle'] == 'on'){
                if (isset($data['customAmount']) && $data['customAmount'] > 0){
                    // test what is returning we need a stripe id
                    $subscription = $this->addRecurringPayment($customer, $data, $paymentForm, $token, $isNew);
                    $stripeId = $subscription->id ?? null;
                }
            }

            if (is_null($stripeId)){
                $charge = $this->stripeCharge($data, $paymentForm, $customer, $isNew, $token, $order);
                $stripeId = $charge['id'] ?? null;
            }
        }

        if (is_null($stripeId)){
            Craft::error('Something went wrong making the charge to Stripe. -CHECK PREVIOUS LOGS-', __METHOD__);
            return $result;
        }

        $order->stripeTransactionId = $stripeId;

        // revert cents
        if ($paymentType != PaymentType::IDEAL){
            $order->totalPrice = $this->convertFromCents($order->totalPrice, $paymentForm->currency);
        }

        $order = $this->finishOrder($order, $paymentForm);

        return $order;
    }

    /**
     * @param $order
     * @param $paymentForm
     * @return null|Order
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
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

        Craft::info('Enupal Stripe - Order Created: './** @scrutinizer ignore-type */ $order->number);
        $result = $order;

        return $result;
    }

    /**
     * @param $planId
     * @param $paymentForm
     * @return null|float
     */
    public function getSetupFeeFromMatrix($planId, $paymentForm)
    {
        foreach ($paymentForm->enupalMultiplePlans as $plan) {
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
     * @param $tax
     * @param $price
     * @return float|int
     */
    private function getTax($tax, $price)
    {
        $total = ($tax / 100) * $price;
        return number_format((float)$total, 2, '.', '');
    }

    /**
     * @param $data
     * @param $paymentForm
     * @param $customer
     * @param $isNew
     * @param $token
     * @param $order
     * @return null|\Stripe\ApiResource
     */
    private function stripeCharge($data, $paymentForm, $customer, $isNew, $token, $order)
    {
        $description = Craft::t('enupal-stripe', 'Order from {email}', ['email' => $data['email']]);
        $charge = null;
        $addressData = $data['address'] ?? null;

        if (!$isNew){
            // Add card or payment method to user
            $customer->sources->create(["source" => $token]);
            // Set as default the new chargeable
            if ($order->paymentType == PaymentType::IDEAL){
                $customer->default_source = $token;
                $customer->save();
            }
        }

        $chargeSettings = [
            'amount' => $data['amount'], // amount in cents from js
            'currency' => $paymentForm->currency,
            'customer' => $customer->id,
            'description' => $description,
            'metadata' => $this->getStripeMetadata($data),
            'shipping' => $addressData ? $this->getShipping($addressData) : []
        ];

        $charge = $this->charge($chargeSettings);

        return $charge;
    }

    /**
     * Stripe Charge given the config array
     *
     * @param $settings
     * @return null|\Stripe\ApiResource
     */
    public function charge($settings)
    {
        $charge = null;

        try {
            $charge = Charge::create($settings);
        } catch (Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            Craft::error('Stripe - declined error occurred: '.json_encode($body), __METHOD__);
            $this->throwException($e);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Craft::error('Stripe - Too many requests made to the API too quickly: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Craft::error('Stripe - Invalid parameters were supplied to Stripe\'s API: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe changed API keys recently)
            Craft::error('Stripe - Authentication with Stripe\'s API failed: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Craft::error('Stripe - Network communication with Stripe failed: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        } catch (\Stripe\Error\Base $e) {
            Craft::error('Stripe - an error occurred: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        } catch (\Exception $e) {
            // Something else happened, completely unrelated to Stripe
            Craft::error('Stripe - something went wrong: '.$e->getMessage(), __METHOD__);
            $this->throwException($e);
        }

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
     * @param $token
     * @param $isNew
     * @param $data
     * @return mixed
     */
    private function addPlanToCustomer($customer, $planId, $token, $isNew, $data)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        //Get the plan from stripe it would trow an exception if the plan does not exists
        Plan::retrieve([
            "id" => $planId
        ]);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planId
        ];

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        if (!$isNew){
            $subscriptionSettings["source"] = $token;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $customer
     * @param $data
     * @param $paymentForm
     * @param $token
     * @param $isNew
     * @return mixed
     */
    private function addRecurringPayment($customer, $data, $paymentForm, $token, $isNew)
    {
        $currentTime = time();
        $planName = strval($currentTime);
        $settings = StripePlugin::$app->settings->getSettings();

        // Remove tax from amount
        if ($settings->enableTaxes && $settings->tax){
            $currentAmount = $this->convertFromCents($data['amount'], $paymentForm->currency);
            $beforeTax = $currentAmount - $data['taxAmount'];
            $data['amount'] = $this->convertToCents($beforeTax, $paymentForm->currency);
        }

        //Create new plan for this customer:
        Plan::create([
            "amount" => $data['amount'],
            "interval" => $paymentForm->recurringPaymentType,
            "product" => [
                "name" => "Plan for recurring payment from: " . $data['email'],
            ],
            "currency" => $paymentForm->currency,
            "id" => $planName
        ]);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planName
        ];

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        if (!$isNew){
            $subscriptionSettings["source"] = $token;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $customer
     * @param $data
     * @param $paymentForm
     * @param $token
     * @param $isNew
     * @return mixed
     */
    private function addCustomPlan($customer, $data, $paymentForm, $token, $isNew)
    {
        $currentTime = time();
        $planName = strval($currentTime);
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

        $data = [
            "amount" => $data['amount'],
            "interval" => $paymentForm->customPlanFrequency,
            "interval_count" => $paymentForm->customPlanInterval,
            "product" => [
                "name" => "Custom Plan from: " . $data['email'],
            ],
            "currency" => $paymentForm->currency,
            "id" => $planName
        ];

        if ($paymentForm->singlePlanTrialPeriod){
            $data['trial_period_days'] = $paymentForm->singlePlanTrialPeriod;
        }

        //Create new plan for this customer:
        Plan::create($data);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planName
        ];

        // Add tax
        if ($settings->enableTaxes && $settings->tax){
            $subscriptionSettings['tax_percent'] = $settings->tax;
        }

        if (!$isNew){
            $subscriptionSettings["source"] = $token;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    public function convertToCents($amount, $currency)
    {
        if ($this->hasZeroDecimals($currency)){
            return (int)$amount;
        }

        return (int)$amount * 100;
    }

    public function convertFromCents($amount, $currency)
    {
        if ($this->hasZeroDecimals($currency)){
            return $amount;
        }

        return $amount / 100;
    }

    /**
     * @param $customer
     * @param $amount
     * @param $paymentForm
     */
    private function addOneTimeSetupFee($customer, $amount, $paymentForm)
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
        $zeroDecimals = ['MGA', 'BIF', 'CLP', 'PYG', 'DJF', 'RWF', 'GNF', 'UGX', 'JPY', 'VND', 'VUV', 'XAF', 'KMF', 'KRW', 'XOF', 'XPF'];

        foreach ($zeroDecimals as $zeroDecimal) {
            if ($zeroDecimal == $currency){
                return true;
            }
        }

        return false;
    }


    /**
     * @param $email
     * @param $token
     * @param $isNew
     * @param bool $testMode
     * @return null|\Stripe\ApiResource|\Stripe\StripeObject
     */
    private function getCustomer($email, $token, &$isNew, $testMode = true)
    {
        $stripeCustomer = null;
        // Check if customer exists
        $customerRecord = CustomerRecord::findOne([
            'email' => $email,
            'testMode' => $testMode
        ]);

        if ($customerRecord){
            $customerId = $customerRecord->stripeId;
            $stripeCustomer = Customer::retrieve($customerId);
        }

        if (!isset($stripeCustomer->id)){
            $stripeCustomer = Customer::create([
                'email' => $email,
                'card' => $token
            ]);

            $customerRecord = new CustomerRecord();
            $customerRecord->email = $email;
            $customerRecord->stripeId = $stripeCustomer->id;
            $customerRecord->testMode = $testMode;
            $customerRecord->save(false);
            $isNew = true;
        }

        return $stripeCustomer;
    }

    private function getStripeMetadata($data)
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
                            $value .= ' - '.$val;
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
                "postal_code" => $postData['postal_code'] ?? '',
                "state" => $postData['state'] ?? '',
            ],
            "carrier" => "", // could also be updated later https://stripe.com/docs/api/php#update_charge
            "tracking_number" => ""
        ];

        return $shipping;
    }

    /**
     * @param $address
     * @return array
     */
    private function getStripeAddress($address)
    {
        $stripeAddress = [];

        $stripeAddress['address_line1'] = $address['line1'];
        $stripeAddress['address_city'] = $address['city'];
        $stripeAddress['address_state'] = $address['state'];
        $stripeAddress['address_zip'] = $address['zip'];
        $stripeAddress['address_country'] = $address['country'];

        return $stripeAddress;
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
