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
 * CommissionPaidEvent class.
 */
class CommissionPaidEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Commission
     */
    public $commission;
}
