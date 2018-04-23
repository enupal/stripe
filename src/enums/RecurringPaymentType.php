<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\enums;

/**
 * Stripe recurring payment types
 */
abstract class RecurringPaymentType extends BaseEnum
{
    // Constants
    // =========================================================================
    const MONTHLY  = 'month';
    const YEARLY = 'year';
}
