<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Stripe shipping options
 */
abstract class ShippingOptions extends BaseEnum
{
    // Constants
    // =========================================================================
    const PROMPT = 0;
    const DONOTPROMPT = 1;
    const PROMPTANDREQUIRE  = 2;
}
