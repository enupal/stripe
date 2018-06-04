<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Stripe Subscription Types
 */
abstract class SubscriptionType extends BaseEnum
{
    // Constants
    // =========================================================================
    const SINGLE_PLAN  = 0;
    const MULTIPLE_PLANS = 1;
}
