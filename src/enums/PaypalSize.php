<?php
/**
 * EnupalStripe plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\stripe\enums;

/**
 * Paypal size button
 */
abstract class PaypalSize extends BaseEnum
{
    // Constants
    // =========================================================================
    const BUYSMALL = 0;
    const BUYBIG = 1;
    const BUYBIGCC = 2;
    const BUYGOLD = 3;
    const PAYSMALL = 4;
    const PAYBIG = 5;
    const PAYBIGCC = 6;
}
