<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\events;

use enupal\stripe\elements\Order;
use yii\base\Event;

/**
 * OrderCompleteEvent class.
 */
class OrderCompleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Order
     */
    public $order;
}
