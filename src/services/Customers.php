<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

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
}
