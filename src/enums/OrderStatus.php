<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Order statuses
 */
abstract class OrderStatus extends BaseEnum
{
    // Constants
    // =========================================================================
    const NEW = 0;
    const PROCESSED = 1;
    const PENDING = 2;
}
