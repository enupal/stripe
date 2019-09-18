<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Checkout Submit Types
 */
abstract class SubmitTypes extends BaseEnum
{
    // Constants
    // =========================================================================
    const AUTO = 'auto';
    const BOOK  = 'book';
    const DONATE  = 'donate';
    const PAY  = 'pay';
}
