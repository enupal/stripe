<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\services;

use Craft;
use craft\mail\Message;
use enupal\stripe\elements\Order;
use enupal\stripe\enums\OrderStatus;
use enupal\stripe\events\OrderCompleteEvent;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\Card;
use Stripe\Stripe;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\records\Order as OrderRecord;
use yii\helpers\Json;

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
        $query->sku($number);
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

        // @todo add support to users change the email template
        $originalPath = $view->getTemplatesPath();
        $view->setTemplatesPath($this->getEmailsPath());
        $htmlBody = $view->renderTemplate('customer', $variables);
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

        // @todo add support to users change the email template
        $originalPath = $view->getTemplatesPath();
        $view->setTemplatesPath($this->getEmailsPath());
        $htmlBody = $view->renderTemplate('admin', $variables);
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

    public function __get($name)
    {
        return parent::__get($name); // TODO: Change the autogenerated stub
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

        if (is_null($token)){
            return false;
        }

        $buttonId = $data['buttonId'] ?? null;

        if (is_null($token) || is_null($buttonId)) {
            Craft::error('Unable to get the stripe token or buttonId', __METHOD__);
            return false;
        }

        $button = StripePlugin::$app->buttons->getButtonById((int)$buttonId);
        $addressData = $data['address'] ?? null;

        if (is_null($button)) {
            throw new \Exception(Craft::t('enupal-stripe','Unable to find the Stripe Button associated to the order'));
        }

        $order = $this->populateOrder($data);
        $order->currency = $button->currency;
        $order->buttonId = $button->id;

        $privateKey = StripePlugin::$app->settings->getPrivateKey();
        Stripe::setAppInfo(StripePlugin::getInstance()->name, StripePlugin::getInstance()->version, StripePlugin::getInstance()->documentationUrl);
        Stripe::setApiKey($privateKey);

        // @todo research if we need create a customer or leave this integration for just orders
        /*$newCustomer = Customer::create([
            'email' => $data['email'],
            'card' => $token
        ]);*/

        $description = Craft::t('enupal-stripe', 'Order from {email}', ['email' => $data['email']]);

        try {
            $charge = Charge::create([
                'amount' => $data['amount'], // amount in cents
                'currency' => $button->currency,
                //'customer' => $newCustomer['id'] ?? null,
                'source' => $token,
                'description' => $description,
                'metadata' => $this->getStripeMetadata($data),
                'shipping' => $addressData ? $this->getShipping($addressData) : []
            ]);

            if (isset($charge['id'])){
                // Stock
                $saveButton = false;
                if (!$button->hasUnlimitedStock && (int)$button->quantity > 0){
                    $button->quantity -= $order->quantity;
                    $saveButton = true;
                }

                $order->stripeTransactionId = $charge['id'];
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
            }
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

        Craft::info('Stripe - Order Created: '.$order->number);

        return true;
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
