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
use phpbu\App\Util\Str;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Error\Card;
use Stripe\Stripe;
use yii\base\Component;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\records\Order as OrderRecord;

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
     * @return Order
     * @throws \Exception
     */
    public function populateOrder()
    {
        $order = new Order();
        $order->orderStatusId = OrderStatus::NEW;
        $order->transactionInfo = json_encode($_POST);
        $order->number = $this->getRandomStr();
        $order->stripeTransactionId = $this->getPostValue('txn_id');
        $order->email = $this->getPostValue('payer_email');
        $order->firstName = $this->getPostValue('first_name');
        $order->lastName = $this->getPostValue('last_name');
        $order->totalPrice = $this->getPostValue('mc_gross');
        $order->currency = $this->getPostValue('mc_currency');
        $order->quantity = $this->getPostValue('quantity');
        $order->shipping = $this->getPostValue('shipping') ?? 0;
        $order->tax = $this->getPostValue('tax') ?? 0;
        $order->discount = $this->getPostValue('discount') ?? 0;
        // Shipping
        $order->addressCity = $this->getPostValue('address_city');
        $order->addressCountry = $this->getPostValue('address_country');
        $order->addressState = $this->getPostValue('address_state');
        $order->addressCountryCode = $this->getPostValue('address_country_code');
        $order->addressName = $this->getPostValue('address_name');
        $order->addressStreet = $this->getPostValue('address_street');
        $order->addressZip = $this->getPostValue('address_zip');
        $order->testMode = 0;
        $order->transactionInfo = json_encode($_POST);
        // Variants
        $variants = [];
        $search = "option_selection";
        $search_length = strlen($search);
        $pos = 1;
        foreach ($_POST as $key => $value) {
            if (substr($key, 0, $search_length) == $search) {
                $name = $_POST['option_name'.$pos] ?? $pos;
                $variants[$name] = $value;
                $pos++;
            }
        }

        $order->variants = json_encode($variants);

        if ($this->getPostValue('test_ipn')) {
            $order->testMode = 1;
        }

        return $order;
    }

    public function __get($name)
    {
        return parent::__get($name); // TODO: Change the autogenerated stub
    }

    /**
     * @throws \Exception
     */
    public function processPayment()
    {
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('enupalStripe');
        $token = $data['token'] ?? null;
        $buttonId = $data['buttonId'] ?? null;

        if ($token && $buttonId) {
            $button = StripePlugin::$app->buttons->getButtonById((int)$buttonId);
            $addressData = $data['address'] ?? null;

            if (is_null($button)) {
                throw new \Exception(Stripe::t('Unable to find the Stripe Button associated to the order'));
            }

            $privateKey = StripePlugin::$app->settings->getPrivateKey();
            Stripe::setApiKey($privateKey);
            // Create new customer
            $newCustomer = Customer::create([
                'email' => $request->getBodyParam($data['email']),
                'card' => $token
            ]);

            $description = Craft::t('enupal-stripe', 'Order from {email}', ['email' => $_POST['email']]);

            try {
                $charge = Charge::create([
                    "amount" => $data['amount'], // amount in cents
                    "currency" => $button->currency,
                    "source" => $token,
                    'customer' => $newCustomer['id'] ?? null,
                    "description" => $description,
                    "metadata" => [],
                    "shipping" => $addressData ? $this->getShipping($addressData) : []
                ]);

                if (isset($charge['id'])){

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
        }
    }

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
     * @param $key
     *
     * @return string|null
     */
    private function getPostValue($key)
    {
        if (!isset($_POST[$key])) {
            return null;
        }

        return $_POST[$key];
    }
}
