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
use craft\elements\User;
use enupal\stripe\elements\Order;
use enupal\stripe\jobs\UpdateEmailAddressOnOrders;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\BillingPortal\Session;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\Subscription;
use yii\base\Component;
use enupal\stripe\records\Customer as CustomerRecord;

class Customers extends Component
{
    /**
     * @param $email
     * @param $stripeId
     * @param bool $testMode
     * @return CustomerRecord
     */
    public function createCustomer($email, $stripeId, $testMode = true)
    {
        $customerRecord = new CustomerRecord();
        $customerRecord->email = $email;
        $customerRecord->stripeId = $stripeId;
        $customerRecord->testMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN);
        $customerRecord->save(false);

        return $customerRecord;
    }

    /**
     * @param $id
     * @return Customer|null
     * @throws \Exception
     */
    public function getStripeCustomer($id)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripeCustomer = null;
        try {
            $stripeCustomer = Customer::retrieve(["id" => $id, "expand" => ["default_source"]]);
        } catch (\Exception $e) {
            Craft::error($e->getMessage() . " - getting a new customer");
        }

        return $stripeCustomer;
    }

    /**
     * @param $email
     * @param $testMode
     * @return Customer|null
     * @throws \Exception
     */
    public function getStripeCustomerByEmail($email, $testMode = null)
    {
        StripePlugin::$app->settings->initializeStripe();
        $settings = StripePlugin::$app->settings->getSettings();
        $testMode = is_null($testMode) ? $settings->testMode : $testMode;
        $stripeCustomer = null;

        $customer = $this->getCustomerByEmail($email, $testMode);

        if (is_null($customer)) {
            return null;
        }

        $stripeCustomer = $this->getStripeCustomer($customer->stripeId);

        return $stripeCustomer;
    }

    /**
     * @param $id
     * @return Invoice|null
     * @throws \Exception
     */
    public function getStripeInvoice($id)
    {
        StripePlugin::$app->settings->initializeStripe();
        $invoice = null;
        try {
            $invoice = Invoice::retrieve($id);
        } catch (\Exception $e) {
            Craft::error($e->getMessage() . " - getting an invoice");
        }

        return $invoice;
    }

    /**
     * @param Customer $customer
     * @param $testMode
     */
    public function registerCustomer(Customer $customer, $testMode)
    {
        $testMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN);
        $customerRecord = CustomerRecord::findOne([
            'email' => $customer['email'],
            'testMode' => $testMode
        ]);

        if ($customerRecord === null) {
            StripePlugin::$app->customers->createCustomer($customer['email'], $customer['id'], $testMode);
        }
    }

    /**
     * @param $customerId
     * @param $returnUrl
     * @return Session|null
     * @throws \Exception
     */
    public function createCustomerPortalSession($customerId, $returnUrl)
    {
        StripePlugin::$app->settings->initializeStripe();
        $session = null;

        try {
            $session = Session::create([
                'customer' => $customerId,
                'return_url' => $returnUrl,
            ]);
        }catch (\Exception $e) {
            Craft::error('Unable to create customer portal session: '.$e->getMessage(), __METHOD__);
        }

        return $session;
    }

    /**
     * @param $customerEmail
     * @param $testMode
     *
     * @return CustomerRecord|null
     */
    public function getCustomerByEmail($customerEmail, $testMode)
    {
        $testMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN);
        $customerRecord = CustomerRecord::findOne([
            'email' => $customerEmail,
            'testMode' => $testMode
        ]);

        return $customerRecord;
    }

    /**
     * @param $id
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function updateStripeCustomer($id, $params)
    {
        StripePlugin::$app->settings->initializeStripe();
        try {
            Customer::update($id, $params);
        } catch (\Exception $e) {
            Craft::error('Unable to update Stripe Customer: ' . $e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * @param $stripeToken
     * @param $customerEmail
     * @return Customer|null
     * @throws \Exception
     */
    public function updateBillingInfo($stripeToken, $customerEmail)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripeCustomer = $this->getStripeCustomerByEmail($customerEmail);

        if (is_null($stripeCustomer)) {
            return null;
        }

        try {
            $stripeCustomer = Customer::update($stripeCustomer->id, [
                'source' => $stripeToken
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            // Use the variable $error to save any errors
            // To be displayed to the customer later in the page
            $body = $e->getJsonBody();
            $err = $body['error'];
            $error = $err['message'];
            Craft::error('Unable to update billing info: ' . $error, __METHOD__);
            return null;
        }

        return $stripeCustomer;
    }

    /**
     * @param $paymentMethodId
     * @param $customerId
     * @return bool
     * @throws \Exception
     */
    public function attachPaymentMethodToCustomer($paymentMethodId, $customerId)
    {
        StripePlugin::$app->settings->initializeStripe();
        $paymentMethod = $this->getPaymentMethod($paymentMethodId);

        if ($paymentMethod) {
            try {
                //  Attach the PaymentMethod to the customer
                $paymentMethod->attach(['customer' => $customerId]);
                // Set a default payment method for future invoices
                Customer::update(
                    $customerId,
                    [
                        'invoice_settings' => ['default_payment_method' => $paymentMethodId],
                    ]
                );

                return true;
            } catch (\Exception $e) {
                Craft::error($e->getMessage(), __METHOD__);
                return false;
            }
        }

        return false;
    }

    /**
     * @param $paymentMethodId
     * @return PaymentMethod|null
     * @throws \Exception
     */
    public function getPaymentMethod($paymentMethodId)
    {
        StripePlugin::$app->settings->initializeStripe();
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return null;
        }

        return $paymentMethod;
    }

    /**
     * @param $subscriptionId
     * @param $planId
     * @return Subscription|null
     * @throws \Exception
     */
    public function updateSubscription($subscriptionId, $planId)
    {
        StripePlugin::$app->settings->initializeStripe();

        try {
            $settings = StripePlugin::$app->settings->getSettings();
            $cancelAtPeriodEnd = $settings->cancelAtPeriodEnd;
            $cancelAtPeriodEnd = filter_var($cancelAtPeriodEnd, FILTER_VALIDATE_BOOLEAN);

            $subscription = Subscription::retrieve($subscriptionId);

            if ($subscription) {
                $subscription = Subscription::update($subscriptionId, [
                    'cancel_at_period_end' => $cancelAtPeriodEnd,
                    'items' => [
                        [
                            'id' => $subscription->items->data[0]->id,
                            'plan' => $planId,
                        ],
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            return null;
        }

        return $subscription;
    }

    /**
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function updateCustomerEmail(User $user)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->updateCustomerEmailOnStripe){
            return false;
        }

        $orders = StripePlugin::$app->orders->getOrdersByUser($user->id);
        $currentEmail = $user->email;

        if (count($orders)) {
            /** @var Order $firstOrder */
            $firstOrder = $orders[0];
            if ($firstOrder->email != $currentEmail) {
                // We need to update the email in Craft and Stripe
                $customerRecord = $this->getCustomerByEmail($firstOrder->email, $settings->testMode);
                $params = [
                    'email' => $currentEmail
                ];
                if ($customerRecord) {
                    $result = $this->updateStripeCustomer($customerRecord->stripeId, $params);

                    if (!$result) {
                        Craft::error('Unable to update customer in Stripe', __METHOD__);
                        return false;
                    }

                    $customerRecord->email = $currentEmail;
                    $customerRecord->save(false);
                    Craft::info('Customer email updated in Stripe', __METHOD__);
                    // Update the new email also in the orders
                    Craft::$app->queue->push(new UpdateEmailAddressOnOrders(
                        [
                            'orders' => $orders,
                            'newEmail' => $currentEmail
                        ]
                    ));
                }
            }
        }

        return true;
    }
}
