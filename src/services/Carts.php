<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\Stripe as StripePlugin;
use yii\base\Component;

class Carts extends Component
{
    const SESSION_CART_NAME = 'enupal_stripe_cart';

    public function getCart()
    {

    }
}
