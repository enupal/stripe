<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\events;

use enupal\stripe\elements\Commission;
use yii\base\Event;

/**
 * CheckoutEvent class.
 */
class CheckoutEvent extends Event
{
    // Properties
    // =========================================================================
    /**
     * @var bool
     */
    public $isCart = false;

    /**
     * @var array
     */
    public $sessionParams;
}
