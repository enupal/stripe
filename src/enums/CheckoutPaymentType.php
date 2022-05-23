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
    const ACH_DIRECT_DEBIT = 'ach_direct_debit';
    const AU_BECS_DEBIT = 'au_becs_debit';
    const KONBINI = 'konbini';
    const US_BANK_ACCOUNT = 'us_bank_account';
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
    const GRAB_PAY = 'grabpay';
    const AFTER_PAY = 'afterpay_clearpay';
    const CANADIAN_DEBIT = 'acss_debit';
    const WECHAT_PAY = 'wechat_pay';
    const BOLETO = 'boleto';
    const OXXO = 'oxxo';
    const KLARNA = 'klarna';
}
