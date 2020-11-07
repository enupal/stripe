<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

/**
 * Stripe Checkout Payment Types
 */
abstract class CheckoutPaymentType extends BaseEnum
{
    // Constants
    // =========================================================================
    const CC = 'card';
    const IDEAL = 'ideal';
    const FPX = 'fpx';
    const ALIPAY = 'alipay';
    const BACS_DEBIT = 'bacs_debit';
    const BANCONTACT = 'bancontact';
    const GIROPAY = 'giropay';
    const P24 = 'p24';
    const EPS = 'eps';
    const SOFORT = 'sofort';
    const SEPA_DEBIT = 'sepa_debit';
}
