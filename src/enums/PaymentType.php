<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Stripe elements Payment Types
 */
abstract class PaymentType extends BaseEnum
{
    // Constants
    // =========================================================================
    const CC = 1;
    const IDEAL  = 2;
    const SOFORT  = 3;
}
