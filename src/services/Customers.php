<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\Stripe as StripePlugin;
use Stripe\Customer;
use Stripe\Invoice;
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
        $customerRecord->testMode = $testMode;
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
        $testMode = is_null($testMode) ? StripePlugin::getInstance()->getSettings()->testMode : $testMode;
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
        $customerRecord = CustomerRecord::findOne([
            'email' => $customer['email'],
            'testMode' => $testMode
        ]);

        if ($customerRecord === null) {
            StripePlugin::$app->customers->createCustomer($customer['email'], $customer['id'], $testMode);
        }
    }

    /**
     * @param $customerEmail
     * @param $testMode
     *
     * @return CustomerRecord|null
     */
    public function getCustomerByEmail($customerEmail, $testMode)
    {
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
}
