<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use enupal\stripe\Stripe as StripePlugin;
use Stripe\Customer;
use yii\base\Component;
use enupal\stripe\records\Customer as CustomerRecord;
use Craft;

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
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function updateStripeCustomer($id, $params)
    {
        StripePlugin::$app->settings->initializeStripe();
        try{
            Customer::update($id, $params);
        }catch (\Exception $e){
            Craft::error('Unable to update Stripe Customer: '.$e->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }
}
