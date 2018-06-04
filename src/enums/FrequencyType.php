<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Stripe recurring payment types
 */
abstract class FrequencyType extends BaseEnum
{
    // Constants
    // =========================================================================
    const YEAR = 'year';
    const MONTH  = 'month';
    const WEEK = 'week';
    const DAY  = 'day';
}
