<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\events;

use enupal\stripe\models\Address;
use yii\base\Event;

class AddressEvent extends Event
{
    /**
     * @var Address The address model
     */
    public $address;

    /**
     * @var bool If this is a new address
     */
    public $isNew;
}
