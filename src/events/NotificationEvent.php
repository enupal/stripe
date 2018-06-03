<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\events;


use yii\base\Event;
use craft\mail\Message;

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
}
