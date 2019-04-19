<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use Craft;
use craft\base\Model;
use Stripe\Coupon;

class CouponRedeemed extends Model
{
    /**
     * @var int ID
     */
    public $isValid = false;

    /**
     * @var Coupon
     */
    public $coupon;

    /**
     * @var float
     */
    public $finalAmount;

    /**
     * @var array
     */
    public $errors = [];

    /**
     * @param $errorMessage
     */
    public function addErrorMessage($errorMessage)
    {
        $this->isValid = false;
        $this->errors[] = $errorMessage;
        Craft::error($errorMessage, __METHOD__);
    }
}
