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

/**
 * WebhookEvent class.
 */
class WebhookEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var mixed
     */
    public $stripeData;
}
