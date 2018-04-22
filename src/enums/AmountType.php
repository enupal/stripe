<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\enums;

/**
 * Stripe amount types
 */
abstract class AmountType extends BaseEnum
{
    // Constants
    // =========================================================================
    const ONE_TIME_SET_AMOUNT  = 0;
    const ONE_TIME_CUSTOM_AMOUNT = 1;
}
