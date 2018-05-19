<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\services;

use Craft;
use craft\helpers\Json;
use craft\mail\Message;
use enupal\stripe\elements\Order;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\enums\SubscriptionType;
use enupal\stripe\events\OrderCompleteEvent;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\Card;
use Stripe\Plan;
use Stripe\Stripe;
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
        $query->handle($number);
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

                $event = new OrderCompleteEvent([
                    'order' => $order
                ]);

                $this->trigger(self::EVENT_AFTER_ORDER_COMPLETE, $event);
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
            OrderStatus::NEW => 'green',
            OrderStatus::SHIPPED => 'blue',
        ];

        return $colors;
    }

    /**
     * @param Order $order
     *
     * @return Order
     */
    public function populateButtonFromPost(Order $order)
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

        try {
            $result = $mailer->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $result = false;
        }

        if ($result) {
            Craft::info('Customer email sent successfully', __METHOD__);
        } else {
            Craft::error('Unable to send customer email', __METHOD__);
        }

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

        try {
            $result = $mailer->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $result = false;
        }

        if ($result) {
            Craft::info('Admin email sent successfully', __METHOD__);
        } else {
            Craft::error('Unable to send admin email', __METHOD__);
        }

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
     * @param array $data
     *
     * @return Order
     * @throws \Exception
     */
    public function populateOrder($data)
    {
        $order = new Order();
        $order->orderStatusId = OrderStatus::NEW;
        $order->number = $this->getRandomStr();
        $order->email = $data['email'];
        $order->totalPrice = $data['amount']/100;// revert cents
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
     * Process Stripe Payment Listener
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    public function processPayment()
    {
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('enupalStripe');
        $token = $data['token'] ?? null;
        $buttonId = $data['buttonId'] ?? null;

        if (is_null($token) || is_null($buttonId)){
            Craft::error('Unable to get the stripe token or buttonId', __METHOD__);
            return false;
        }

        $button = StripePlugin::$app->buttons->getButtonById((int)$buttonId);

        if (is_null($button)) {
            throw new \Exception(Craft::t('enupal-stripe','Unable to find the Stripe Button associated to the order'));
        }

        $order = $this->populateOrder($data);
        $order->currency = $button->currency;
        $order->buttonId = $button->id;

        StripePlugin::$app->settings->initializeStripe();

        $isNew = false;
        $customer = $this->getCustomer($data, $token, $isNew);
        $charge = null;
        $stripeId = null;

        if ($button->enableSubscriptions){
            $planId = null;

            if ($button->subscriptionType == SubscriptionType::SINGLE_PLAN && !$button->enableCustomPlanAmount){
                $plan = Json::decode($button->singlePlanInfo, true);
                $planId = $plan['id'];

                // Either single plan or multiple plans the user should select one plan and plan id should be available in the post request
                $subscription = $this->addPlanToCustomer($customer, $planId, $token, $isNew, $data);
                $stripeId = $subscription->id ?? null;
            }

            if ($button->subscriptionType == SubscriptionType::SINGLE_PLAN && $button->enableCustomPlanAmount) {
                if (isset($data['customPlanAmount']) && $data['customPlanAmount'] > 0){
                    // test what is returning we need a stripe id
                    $subscription = $this->addCustomPlan($customer, $data, $button, $token, $isNew);
                    $stripeId = $subscription->id ?? null;
                }
            }

            if ($button->subscriptionType == SubscriptionType::MULTIPLE_PLANS) {
                $planId = $data['enupalMultiPlan'] ?? null;

                if (is_null($planId) || empty($planId)){
                    throw new \Exception(Craft::t('enupal-stripe','Plan Id is required'));
                }

                $subscription = $this->addPlanToCustomer($customer, $planId, $token, $isNew, $data);
                $stripeId = $subscription->id ?? null;
            }
        }else{
            // One time payment could be a subscription
            if (isset($data['recurringToggle']) && $data['recurringToggle'] == 'on'){
                if (isset($data['customAmount']) && $data['customAmount'] > 0){
                    // test what is returning we need a stripe id
                    $subscription = $this->addRecurringPayment($customer, $data, $button, $token, $isNew);
                    $stripeId = $subscription->id ?? null;
                }
            }

            if (is_null($stripeId)){
                $charge = $this->stripeCharge($data, $button, $customer, $isNew, $token);
                $stripeId = $charge['id'] ?? null;
            }
        }

        if (is_null($stripeId)){
            Craft::error('Something went wrong making the charge to Stripe. -CHECK PREVIOUS LOGS-', __METHOD__);
            return false;
        }

        // Stock
        $saveButton = false;
        if (!$button->hasUnlimitedStock && (int)$button->quantity > 0){
            $button->quantity -= $order->quantity;
            $saveButton = true;
        }

        $order->stripeTransactionId = $stripeId;
        // Finally save the order in Craft CMS
        if (!StripePlugin::$app->orders->saveOrder($order)){
            Craft::error('Something went wrong saving the Stripe Order: '.json_encode($order->getErrors()), __METHOD__);
            return false;
        }

        // Let's update the stock
        if ($saveButton){
            if (!StripePlugin::$app->buttons->saveButton($button)){
                Craft::error('Something went wrong updating the stripe button stock: '.json_encode($button->getErrors()), __METHOD__);
                return false;
            }
        }

        Craft::info('Enupal Stripe - Order Created: '.$order->number);

        return true;
    }

    private function stripeCharge($data, $button, $customer, $isNew, $token)
    {
        $description = Craft::t('enupal-stripe', 'Order from {email}', ['email' => $data['email']]);
        $charge = null;
        $addressData = $data['address'] ?? null;

        try {
            $chargeSettings = [
                'amount' => $data['amount'], // amount in cents
                'currency' => $button->currency,
                'customer' => $customer->id,
                'description' => $description,
                'metadata' => $this->getStripeMetadata($data),
                'shipping' => $addressData ? $this->getShipping($addressData) : []
            ];

            if (!$isNew){
                // @todo we have duplicate card issues
                //$chargeSettings['source'] = $token;
                // Add card or payment method to user
                $customer->sources->create(["source" => $token]);
            }

            $charge = Charge::create($chargeSettings);

        } catch (Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            Craft::error('Stripe - declined error occurred: '.json_encode($body));
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            Craft::error('Stripe - Too many requests made to the API too quickly: '.$e->getMessage());
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            Craft::error('Stripe - Invalid parameters were supplied to Stripe\'s API: '.$e->getMessage());
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe changed API keys recently)
            Craft::error('Stripe - Authentication with Stripe\'s API failed: '.$e->getMessage());
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            Craft::error('Stripe - Network communication with Stripe failed: '.$e->getMessage());
        } catch (\Stripe\Error\Base $e) {
            Craft::error('Stripe - an error occurred: '.$e->getMessage());
        } catch (\Exception $e) {
            // Something else happened, completely unrelated to Stripe
            Craft::error('Stripe - something went wrong: '.$e->getMessage());
        }

        return $charge;
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
        //Get the plan from stripe it would trow an exception if the plan does not exists
        Plan::retrieve([
            "id" => $planId
        ]);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planId
        ];

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
     * @param $button
     * @param $token
     * @param $isNew
     * @return mixed
     */
    private function addRecurringPayment($customer, $data, $button, $token, $isNew)
    {
        $currentTime = time();
        $planName = strval($currentTime);

        //Create new plan for this customer:
        Plan::create([
            "amount" => $data['amount'],
            "interval" => $button->recurringPaymentType,
            "product" => [
                "name" => "Plan for recurring payment from: " . $data['email'],
            ],
            "currency" => $button->currency,
            "id" => $planName
        ]);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planName
        ];

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
     * @param $button
     * @param $token
     * @param $isNew
     * @return mixed
     */
    private function addCustomPlan($customer, $data, $button, $token, $isNew)
    {
        $currentTime = time();
        $planName = strval($currentTime);

        //Create new plan for this customer:
        Plan::create([
            "amount" => $data['amount'],
            "interval" => $button->customPlanFrequency,
            "interval_count" => $button->customPlanInterval,
            "product" => [
                "name" => "Custom Plan from: " . $data['email'],
            ],
            "trial_period_days" => $button->singlePlanTrialPeriod ?? '',
            "currency" => $button->currency,
            "id" => $planName
        ]);

        // Add the plan to the customer
        $subscriptionSettings = [
            "plan" => $planName
        ];

        if (!$isNew){
            $subscriptionSettings["source"] = $token;
        }

        $subscriptionSettings['metadata'] = $this->getStripeMetadata($data);

        $subscription = $customer->subscriptions->create($subscriptionSettings);

        return $subscription;
    }

    /**
     * @param $data
     * @param $token
     * @param $isNew
     * @return \Stripe\ApiResource|\Stripe\StripeObject
     */
    private function getCustomer($data, $token, &$isNew)
    {
        $email = $data['email'] ?? null;
        $stripeCustomer = null;
        // Check if customer exists
        $customerRecord = CustomerRecord::findOne([
            'email' => $email,
            'testMode' => $data['testMode']
        ]);

        if ($customerRecord){
            $customerId = $customerRecord->stripeId;
            $stripeCustomer = Customer::retrieve($customerId);
        }

        if (!isset($stripeCustomer->id)){
            $stripeCustomer = Customer::create([
                'email' => $data['email'],
                'card' => $token
            ]);

            $customerRecord = new CustomerRecord();
            $customerRecord->email = $data['email'];
            $customerRecord->stripeId = $stripeCustomer->id;
            $customerRecord->testMode = $data['testMode'];
            $customerRecord->save(false);
            $isNew = true;
        }

        return $stripeCustomer;
    }

    private function getStripeMetadata($data)
    {
        $metadata = [];
        if (isset($data['metadata'])){
            foreach ($data['metadata']as $key => $item) {
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
}
