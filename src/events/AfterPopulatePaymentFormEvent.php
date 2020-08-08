<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\events;

use enupal\stripe\elements\PaymentForm;
use yii\base\Event;

/**
 * AfterPopulatePaymentFormEvent class.
 */
class AfterPopulatePaymentFormEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var PaymentForm
     */
    public $paymentForm;
}
