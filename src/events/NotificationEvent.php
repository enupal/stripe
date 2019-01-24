<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\events;


use yii\base\Event;
use craft\mail\Message;
use enupal\stripe\elements\Order;

/**
 * NotificationEvent class.
 */
class NotificationEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Message
     */
    public $message;

    /**
     * @var string admin|customer
     */
    public $type;

    /**
     * @var Order
     */
    public $order;
}
