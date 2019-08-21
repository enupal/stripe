<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\models;

use craft\base\Model;

class CustomPlan extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $amountInCents;

    /**
     * @var string
     */
    public $interval;

    /**
     * @var int
     */
    public $intervalCount;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var int
     */
    public $trialPeriodDays;

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }
}
