<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\enums;

/**
 * Stripe discount types
 */
abstract class DiscountType extends BaseEnum
{
    // Constants
    // =========================================================================
    const RATE  = 0;
    const AMOUNT = 1;
}
