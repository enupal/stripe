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
 * OrderCaptureEvent class.
 */
class OrderCaptureEvent extends Event
{
    /**
     * @var Order
     */
    public $order;
}
